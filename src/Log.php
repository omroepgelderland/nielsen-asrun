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
    private ?\DateTime $created;
    /** @var list<Entry> */
    private $entries;

    /**
     * @param array{
     *     type: LogType,
     *     typology_source?: TypologySource,
     *     encoding?: string,
     *     broadcast_day: \DateTime,
     *     author: string,
     *     channel_name: string,
     *     channel_abbreviation: string,
     *     created?: \DateTime
     * } $params
     */
    private function __construct( array $params ) {
        $this->type = $params['type'];
        if ( isset($params['typology_source']) ) {
            $this->typology_source = $params['typology_source'];
        } else {
            $this->typology_source = TypologySource::None;
        }
        $this->encoding = \strtoupper($params['encoding'] ?? 'iso-8859-1');
        $this->broadcast_day = $params['broadcast_day'];
        $this->author = $params['author'];
        $this->channel_name = $params['channel_name'];
        $this->channel_abbreviation = $params['channel_abbreviation'];
        $this->created = $params['created'] ?? null;
        $this->entries = [];
    }

    /**
     * Create a new AsRunLog.
     * 
     * Options:
     * typology_source (optional): Current possible values are therefore NMO or
     * NONE.
     * encoding (optional, defaults to iso-8859-1): The name of the used
     * character set. We accept only character sets with one byte per character.
     * Only characters common to the desired charset *and* to ISO-8859-1 should
     * be used (in other words, ISO-8859-1 gives the set of allowed characters,
     * but other encodings are allowed).
     * broadcast_day: The date of the broadcasts.
     * author: file author like NPO, RTL, TALPA, ...in general the Channelgroup.
     * channel_name: The channel name. Maximum length of 6 characters.
     * channel_abbreviation: channel abbreviation for filename extension.
     * created (optional): Creation date.
     * 
     * @param array{
     *     typology_source?: TypologySource,
     *     encoding?: string,
     *     broadcast_day: \DateTime,
     *     author: string,
     *     channel_name: string,
     *     channel_abbreviation: string,
     *     created?: \DateTime
     * } $params
     */
    public static function create_asrunlog( array $params ): self {
        return new self([
            'type' => LogType::AsRunLog,
            ...$params
        ]);
    }

    /**
     * Create a new Program Before file.
     * 
     * Options:
     * typology_source (optional): Current possible values are therefore NMO or
     * NONE.
     * encoding (optional, defaults to iso-8859-1): The name of the used
     * character set. We accept only character sets with one byte per character.
     * Only characters common to the desired charset *and* to ISO-8859-1 should
     * be used (in other words, ISO-8859-1 gives the set of allowed characters,
     * but other encodings are allowed).
     * broadcast_day: The date of the broadcasts.
     * author: file author like NPO, RTL, TALPA, ...in general the Channelgroup.
     * channel_name: The channel name. Maximum length of 6 characters.
     * channel_abbreviation: channel abbreviation for filename extension.
     * created (optional): Creation date.
     * 
     * @param array{
     *     typology_source?: TypologySource,
     *     encoding?: string,
     *     broadcast_day: \DateTime,
     *     author: string,
     *     channel_name: string,
     *     channel_abbreviation: string,
     *     created?: \DateTime
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
            ->setTime(02, 0, 0, 0);
    }

    /**
     * Add a new entry to the log.
     * @throws TimeOutsideBounds If the start time or end time is outside of the
     * range of the logfile.
     */
    public function add_entry( Entry $entry ): void {
        if (
            $entry->get_starttime() < $this->get_starttime()
            || $entry->get_endtime() > $this->get_endtime()
        ) {
            throw new TimeOutsideBounds(
                "Entry times {$entry->get_starttime()->format('c')}–{$entry->get_endtime()->format('c')} are outide of the "
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
        $created = $this->created ?? new \DateTime();
        $padded_channel = \str_pad(\substr($this->channel_name, 0, 6), 6);
        return [
            [
                '%FORMAT',
                self::FORMAT_IDENTIFIER,
                'BROADCASTDAY',
                $this->broadcast_day->format('Ymd'),
                'CREATED',
                $created->format('Ymd'),
                'AT',
                $created->format('His'),
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
        $lines[] = [];

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

    private function usort_cmp( Entry $a, Entry $b ): int {
        if ( $a->get_starttime() == $b->get_starttime() ) {
            return 0;
        }
        return ( $a->get_starttime() < $b->get_starttime() ) ? -1 : 1;
    }
    
    /**
     * Merges consecutive break entries into single break entries.
     * Only the metadata of the first break in the block is preserved.
     */
    public function merge_breaks(): void {
        $this->sort();

        for ( $i = count($this->entries) - 1; $i > 0; $i-- ) {
            $entry = $this->entries[$i];
            $prev_entry = $this->entries[$i-1];
            if (
                $entry->program_type === ProgramType::Break
                && $prev_entry->program_type === ProgramType::Break
                && $entry->channel_id === $prev_entry->channel_id
                && $entry->omroepen == $prev_entry->omroepen
            ) {
                $prev_entry->set_endtime($entry->get_endtime());
                \array_splice($this->entries, $i, 1);
            }
        }
    }

    /**
     * Merges consecutive program entries with the same prog_id together.
     * Only the metadata of the first program in the block is preserved.
     */
    public function merge_programs(): void {
        $this->sort();

        for ( $i = count($this->entries) - 1; $i > 0; $i-- ) {
            $entry = $this->entries[$i];
            $prev_entry = $this->entries[$i-1];
            if (
                $entry->program_type === ProgramType::Programma
                && $prev_entry->program_type === ProgramType::Programma
                && $entry->prog_id === $prev_entry->prog_id
                && $entry->channel_id === $prev_entry->channel_id
                && $entry->omroepen == $prev_entry->omroepen
            ) {
                $prev_entry->set_endtime($entry->get_endtime());
                \array_splice($this->entries, $i, 1);
            }
        }
    }

    /**
     * Fixes entries where the end time is later than the start time of the next
     * entry.
     */
    private function fix_overlaps(): void {
        foreach ( $this->entries as $i => $entry ) {
            if ( $i+1 < count($this->entries) ) {
                $next_start = $this->entries[$i+1]->get_starttime();
            } else {
                $next_start = $this->get_endtime();
            }
            if ( $entry->get_endtime() >= $next_start ) {
                $entry->set_endtime($next_start);
            }
        }
    }

    /**
     * Fill all time gaps. All gaps are added to the preceding entry.
     * If there is a gap at the start of the log the start of the first entry is
     * changed to the log start time.
     * If there is a gap at the end the last entry is extended until the log end
     * time.
     */
    public function fill_gaps(): void {
        $this->sort();
        for ( $i = 0; $i < count($this->entries) - 1; $i++ ) {
            $entry = $this->entries[$i];
            $next_entry = $this->entries[$i+1];
            $entry->set_endtime($next_entry->get_starttime());
        }
        $this->entries[0]->set_starttime($this->get_starttime());
        $this->entries[count($this->entries) - 1]->set_endtime($this->get_endtime());
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
