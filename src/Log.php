<?php

namespace nielsen_asrun;

/**
 * AsRunLog file for Nielsen Hybrid Audit.
 */
class Log {

    const FORMAT_IDENTIFIER = '20230101';

    private LogType $type;
    private TypologySource $typology_source;
    private string $encoding;
    private \DateTime $broadcast_day;
    private string $author;
    private string $channel_name;
    private string $channel_abbreviation;
    /** @var list<AsRunEntry> */
    private $entries;

    /**
     * @param array{
     *     type: LogType,
     *     typology_source?: TypologySource,
     *     encoding: string,
     *     broadcast_day: \DateTime,
     *     author: string,
     *     channel_name: string,
     *     channel_abbreviation: string
     * } $params
     */
    private function __construct( array $params ) {
        $this->type = $params['type'];
        if ( isset($params['typology_source']) ) {
            $this->typology_source = $params['typology_source'];
        } else {
            $this->typology_source = TypologySource::None;
        }
        $this->encoding = \strtoupper($params['encoding']);
        $this->broadcast_day = $params['broadcast_day'];
        $this->author = $params['author'];
        $this->channel_name = $params['channel_name'];
        $this->channel_abbreviation = $params['channel_abbreviation'];
        $this->entries = [];
    }

    /**
     * @param array{
     *     typology_source?: TypologySource,
     *     encoding: string,
     *     broadcast_day: \DateTime,
     *     author: string,
     *     channel_name: string,
     *     channel_abbreviation: string
     * } $params
     */
    public static function create_asrunlog( array $params ): self {
        return new self([
            'type' => LogType::AsRunLog,
            ...$params
        ]);
    }

    /**
     * @param array{
     *     typology_source?: TypologySource,
     *     encoding: string,
     *     broadcast_day: \DateTime,
     *     author: string,
     *     channel_name: string,
     *     channel_abbreviation: string
     * } $params
     */
    public static function create_program_before( array $params ): self {
        return new self([
            'type' => LogType::ProgramBefore,
            ...$params
        ]);
    }

    /**
     * The start time of the log is 02:00:00 on the broadcastdate.
     */
    public function get_starttime(): \DateTime {
        return (clone $this->broadcast_day)->setTime(2, 0, 0, 0);
    }

    /**
     * The end time  of the log is 01:59:59 on the day after the broadcastdate.
     */
    public function get_endtime(): \DateTime {
        return (clone $this->broadcast_day)
            ->add(new \DateInterval('P1D'))
            ->setTime(01, 59, 59, 0);
    }

    /**
     * Add a new entry to the log.
     * @throws TimeOutsideBounds If the start time or end time is outside of the
     * range of the logfile.
     */
    public function add_entry( AsRunEntry $entry ): void {
        if (
            $entry->starttime < $this->get_starttime()
            || $entry->endtime > $this->get_endtime()
        ) {
            throw new TimeOutsideBounds(
                "Entry times {$entry->starttime->format('c')}–{$entry->endtime->format('c')} are outide of the "
                ."broadcastday {$this->get_starttime()->format('c')}–{$this->get_endtime()->format('c')}"
            );
        }
        $this->entries[] = $entry;
    }
    
    /**
     * Returns the output filename of the log.
     */
    public function get_filename(): string {
        $type = $this->type === LogType::AsRunLog ? 'AR' : 'BR';
        return "{$type}{$this->broadcast_day->format('Ymd')}.{$this->channel_abbreviation}";
    }

    /**
     * @return list<list<string>>
     */
    private function get_header_lines() {
        $nu = new \DateTime();
        $padded_channel = \str_pad(\substr($this->channel_name, 0, 6), 6);
        return [
            [
                '%FORMAT',
                self::FORMAT_IDENTIFIER,
                'BROADCASTDAY',
                $this->broadcast_day->format('Ymd'),
                'CREATED',
                $nu->format('Ymd'),
                'AT',
                $nu->format('His'),
                $this->type === LogType::AsRunLog ? 'BY' : 'CHANNEL',
                $padded_channel
            ],
            ['%COLLATING_SEQUENCE', $this->encoding],
            ['%AUTHOR', $this->author],
            ['%FILETYPE', $this->type === LogType::AsRunLog ? 'AsRun' : 'BEFORE_BROADCAST'],
            ['%OTHERFIELDS', ''],
            ['%PIVOTHOUR', '020000'],
            ['%TYPOLOGYSOURCE', $this->typology_source->value],
            [
                '%COMMENT ChannelID',
                'Omroep',
                'Date',
                $this->type === LogType::AsRunLog ? 'StartTime' : 'BeginTime',
                'Duration',
                'EndTime',
                'ProgID',
                'ProgramType',
                'UnharmonizedTitle',
                'SubTitle',
                'PromoTypeID',
                'SecondaryUnharmonizedTitle',
                'TertiaryUnharmonizedTitle',
                'PromotionChannelID',
                'PromotionDay',
                'RepeatCode',
                'ReconciliationKey',
                'ProgramTypology',
                'CCC',
                'PromoID'
            ]
        ];
    }

    /**
     * Returns the parsed contents of the log.
     */
    public function __toString(): string {
        $this->sort();
        $this->fix_overlaps();

        $lines = $this->get_header_lines();
        $lines = \array_merge($lines, \array_map(fn($entry) => $entry->to_array(), $this->entries));
        $lines[] = [''];

        $csv_lines = \array_map(fn($line) => \implode("\t", $line), $lines);
        $data = \implode("\r\n", $csv_lines);
        return \iconv(
            \iconv_get_encoding()['input_encoding'],
            "{$this->encoding}//TRANSLIT",
            $data
        );
    }

    private function sort(): void {
        \usort($this->entries, $this->usort_cmp(...));
    }

    private function usort_cmp( AsRunEntry $a, AsRunEntry $b ): int {
        if ( $a->starttime == $b->starttime ) {
            return 0;
        }
        return ( $a->starttime < $b->starttime ) ? -1 : 1;
    }
    
    /**
     * Merges consecutive break entries into single break entries.
     */
    public function merge_breaks(): void {
        $this->sort();

        // Remove consecutive breaks, leaving only the first.
        for ( $i = count($this->entries) - 1; $i > 0; $i-- ) {
            $entry = $this->entries[$i];
            $prev_entry = $this->entries[$i-1];
            if ( $entry->program_type === ProgramType::Break && $prev_entry->program_type === ProgramType::Break ) {
                \array_splice($this->entries, $i, 1);
            }
        }

        // Make the start time and end time connect to the previous and next
        // entries.
        foreach ( $this->entries as $i => $entry ) {
            $second = new \DateInterval('PT1S');
            if ( $entry->program_type === ProgramType::Break ) {
                if ( $i > 0 ) {
                    $prev_end = clone ($this->entries[$i-1]->endtime);
                } else {
                    $prev_end = $this->get_starttime();
                }
                if ( $i+1 < count($this->entries) ) {
                    $next_start = clone ($this->entries[$i+1]->starttime);
                } else {
                    $next_start = $this->get_endtime();
                }
                $entry->starttime = $prev_end->add($second);
                $entry->endtime = $next_start->sub($second);
            }
        }
    }

    /**
     * Fixes entries where the end time is later than the start time of the next
     * entry.
     */
    private function fix_overlaps(): void {
        $second = new \DateInterval('PT1S');
        foreach ( $this->entries as $i => $entry ) {
            if ( $i+1 < count($this->entries) ) {
                $next_start = clone ($this->entries[$i+1]->starttime);
            } else {
                $next_start = $this->get_endtime()->add($second);
            }
            if ( $entry->endtime >= $next_start ) {
                $entry->endtime = $next_start->sub($second);
            }
        }
    }

    /**
     * Formats a datetime into a string where times between 00:00 and 02:00 are
     * considered to be on the previous date.
     */
    public static function format_theoretical_date( \DateTime $dt ): string {
        $dt = clone $dt;
        if ( $dt->format('H') < 2 ) {
            $dt->sub(new \DateInterval('P1D'));
        }
        return $dt->format('Ymd');
    }

    /**
     * Formats a timestamp where times between 00:00 and 02:00 are displayed as
     * 24:00 to 25:59
     */
    public static function format_theoretical_time( \DateTime $dt ): string {
        $dt = clone $dt;
        if ( $dt->format('H') < 2 ) {
            $hour = $dt->format('H') + 24;
            return "{$hour}{$dt->format('is')}";
        } else {
            return $dt->format('His');
        }
    }

}
