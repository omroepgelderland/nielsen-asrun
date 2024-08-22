<?php
/**
 * @author Remy Glaser <rglaser@gld.nl>
 */

namespace nielsen_asrun;

/**
 * Single line in an AsRunLog file.
 * 
 * @phpstan-type Params array{
 *     channel_id: int,
 *     omroepen: list<string>,
 *     starttime: \DateTime,
 *     endtime: \DateTime,
 *     prog_id?: ?string,
 *     program_type: ProgramType,
 *     unharmonized_title: string,
 *     sub_title?: ?string,
 *     promo_type_id?: ?PromoType,
 *     secondary_unharmonized_title?: ?string,
 *     tertiary_unharmonized_title?: ?string,
 *     promotion_channel_id?: ?int,
 *     promotion_day?: ?int,
 *     repeat_code?: ?RepeatCode,
 *     reconciliation_key?: ?string,
 *     program_typology?: ?string,
 *     ccc?: ?string,
 *     promo_id?: ?string
 * }
 */
class Entry {

    public int $channel_id;
    /** @var list<string> */
    public array $omroepen;
    private \DateTime $starttime;
    private \DateTime $endtime;
    public ?string $prog_id;
    public ProgramType $program_type;
    public string $unharmonized_title;
    public ?string $sub_title;
    public ?PromoType $promo_type_id;
    public ?string $secondary_unharmonized_title;
    public ?string $tertiary_unharmonized_title;
    public ?int $promotion_channel_id;
    public ?int $promotion_day;
    public ?RepeatCode $repeat_code;
    public ?string $reconciliation_key;
    public ?string $program_typology;
    public ?string $ccc;
    public ?string $promo_id;

    /**
     * @param Params $data
     */
    private function __construct( array $data ) {
        $this->channel_id = $data['channel_id'];
        $this->omroepen = $data['omroepen'];
        $this->starttime = $data['starttime'];
        $this->endtime = $data['endtime'];
        $this->prog_id = $data['prog_id'] ?? null;
        $this->program_type = $data['program_type'];
        $this->unharmonized_title = $data['unharmonized_title'];
        $this->sub_title = $data['sub_title'] ?? null;
        $this->promo_type_id = $data['promo_type_id'] ?? null;
        $this->secondary_unharmonized_title = $data['secondary_unharmonized_title'] ?? null;
        $this->tertiary_unharmonized_title = $data['tertiary_unharmonized_title'] ?? null;
        $this->promotion_channel_id = $data['promotion_channel_id'] ?? null;
        $this->promotion_day = $data['promotion_day'] ?? null;
        $this->repeat_code = $data['repeat_code'] ?? null;
        $this->reconciliation_key = $data['reconciliation_key'] ?? null;
        $this->program_typology = $data['program_typology'] ?? null;
        $this->ccc = $data['ccc'] ?? null;
        $this->promo_id = $data['promo_id'] ?? null;
    }

    public function get_starttime(): \DateTime {
        return clone $this->starttime;
    }

    public function get_endtime(): \DateTime {
        return clone $this->endtime;
    }

    public function set_starttime( \DateTime $dt ): void {
        $this->starttime = clone $dt;
    }

    public function set_endtime( \DateTime $dt ): void {
        $this->endtime = clone $dt;
    }

    /**
     * Create a new entry for a program.
     * 
     * options:
     * channel_id: Channel ID.
     * omroepen: ‘Omroep’ name – Omroep (or omroep combination) claiming the
     * editorial responsibility of the program.
     * starttime: Start of the entry.
     * endtime: End of the entry. Unlike the output format the end time does not
     * include the following second. For instance, if the next entry starts at
     * 17:00, this entry ends at 17:00, not 16:59.
     * prog_id: Channel unique identifier for the program. Please contact NMO
     * for definitions, may be channel specific.
     * unharmonized_title: Program title. Normally it should be the title the
     * channel wants to publish. Also used for the internal promo title.
     * sub_title (optional): Subtitle the channel wants to publish.
     * secondary_unharmonized_title (optional):
     * tertiary_unharmonized_title (optional):
     * repeat_code: First broadcast or repeat.
     * reconciliation_key (optional): Key from the broadcaster. If the event is
     * linked, Nielsen will provide the same key.
     * program_typology (optional): NMO Program Typology.
     * ccc (optional): Content Classification Code for NPO.
     * 
     * @param array{
     *     channel_id: int,
     *     omroepen: list<string>,
     *     starttime: \DateTime,
     *     endtime: \DateTime,
     *     prog_id: string,
     *     unharmonized_title: string,
     *     sub_title?: ?string,
     *     secondary_unharmonized_title?: ?string,
     *     tertiary_unharmonized_title?: ?string,
     *     repeat_code: RepeatCode,
     *     reconciliation_key?: ?string,
     *     program_typology?: ?string,
     *     ccc?: ?string
     * } $data
     */
    public static function create_program_entry( $data ): self {
        /** @phpstan-ignore isset.offset */
        if ( !isset($data['prog_id']) ) {
            throw new ASRunException(
                'Field prog_id is mandatory for ProgramType Programma'
            );
        }
        /** @phpstan-ignore isset.offset */
        if ( !isset($data['repeat_code']) ) {
            throw new ASRunException(
                'Field repeat_code is mandatory for ProgramType Programma'
            );
        }
        return new self([
            'program_type' => ProgramType::Programma,
            ...$data
        ]);
    }

    /**
     * Create a new entry for a promotion.
     * 
     * options:
     * channel_id: Channel ID.
     * omroepen: ‘Omroep’ name – Omroep (or omroep combination) claiming the
     * editorial responsibility of the program.
     * starttime: Start of the entry.
     * endtime: End of the entry. Unlike the output format the end time does not
     * include the following second. For instance, if the next entry starts at
     * 17:00, this entry ends at 17:00, not 16:59.
     * unharmonized_title: Program title. Normally it should be the title the
     * channel wants to publish. Also used for the internal promo title.
     * sub_title (optional): Subtitle the channel wants to publish.
     * promo_type_id: Classification type of the promos.
     * secondary_unharmonized_title (optional):
     * tertiary_unharmonized_title (optional):
     * promotion_channel_id (optional):
     * promotion_day (optional):
     * repeat_code (optional): First broadcast or repeat.
     * reconciliation_key (optional): Key from the broadcaster. If the event is
     * linked, Nielsen will provide the same key.
     * ccc (optional): Content Classification Code for NPO.
     * promo_id (optional): Broadcaster unique identifier for the promo. Should
     * be unique per promo creative.
     * 
     * @param array{
     *     channel_id: int,
     *     omroepen: list<string>,
     *     starttime: \DateTime,
     *     endtime: \DateTime,
     *     unharmonized_title: string,
     *     sub_title?: ?string,
     *     promo_type_id: PromoType,
     *     secondary_unharmonized_title?: ?string,
     *     tertiary_unharmonized_title?: ?string,
     *     promotion_channel_id?: ?int,
     *     promotion_day?: ?int,
     *     repeat_code?: ?RepeatCode,
     *     reconciliation_key?: ?string,
     *     ccc?: ?string,
     *     promo_id?: ?string
     * } $data
     */
    public static function create_promo_entry( $data ): self {
        /** @phpstan-ignore isset.offset */
        if ( !isset($data['promo_type_id']) ) {
            throw new ASRunException(
                'Field promo_type_id is mandatory for ProgramType Promo'
            );
        }
        return new self([
            'program_type' => ProgramType::Promo,
            ...$data
        ]);
    }

    /**
     * Create a new entry for a Station ID fragment.
     * 
     * options:
     * channel_id: Channel ID.
     * omroepen: ‘Omroep’ name – Omroep (or omroep combination) claiming the
     * editorial responsibility of the program.
     * starttime: Start of the entry.
     * endtime: End of the entry. Unlike the output format the end time does not
     * include the following second. For instance, if the next entry starts at
     * 17:00, this entry ends at 17:00, not 16:59.
     * unharmonized_title: Program title. Normally it should be the title the
     * channel wants to publish. Also used for the internal promo title.
     * sub_title (optional): Subtitle the channel wants to publish.
     * secondary_unharmonized_title (optional):
     * tertiary_unharmonized_title (optional):
     * reconciliation_key (optional): Key from the broadcaster. If the event is
     * linked, Nielsen will provide the same key.
     * ccc (optional): Content Classification Code for NPO.
     * 
     * @param array{
     *     channel_id: int,
     *     omroepen: list<string>,
     *     starttime: \DateTime,
     *     endtime: \DateTime,
     *     unharmonized_title: string,
     *     sub_title?: ?string,
     *     secondary_unharmonized_title?: ?string,
     *     tertiary_unharmonized_title?: ?string,
     *     reconciliation_key?: ?string,
     *     ccc?: ?string
     * } $data
     */
    public static function create_station_id_entry( $data): self {
        return new self([
            'program_type' => ProgramType::StationID,
            ...$data
        ]);
    }

    /**
     * Create a new entry for a break.
     * 
     * options:
     * channel_id: Channel ID.
     * omroepen: ‘Omroep’ name – Omroep (or omroep combination) claiming the
     * editorial responsibility of the program.
     * starttime: Start of the entry.
     * endtime: End of the entry. Unlike the output format the end time does not
     * include the following second. For instance, if the next entry starts at
     * 17:00, this entry ends at 17:00, not 16:59.
     * unharmonized_title: Break code.
     * sub_title (optional): Subtitle the channel wants to publish.
     * secondary_unharmonized_title (optional):
     * tertiary_unharmonized_title (optional):
     * reconciliation_key (optional): Key from the broadcaster. If the event is
     * linked, Nielsen will provide the same key.
     * ccc (optional): Content Classification Code for NPO.
     * 
     * @param array{
     *     channel_id: int,
     *     omroepen: list<string>,
     *     starttime: \DateTime,
     *     endtime: \DateTime,
     *     unharmonized_title: string,
     *     sub_title?: ?string,
     *     secondary_unharmonized_title?: ?string,
     *     tertiary_unharmonized_title?: ?string,
     *     reconciliation_key?: ?string,
     *     ccc?: ?string
     * } $data
     */
    public static function create_break_entry( $data ): self {
        return new self([
            'program_type' => ProgramType::Break,
            ...$data
        ]);
    }

    /**
     * Return the log entry line as an array.
     * @return list<string>
     */
    public function to_array() {
        $unharmonized_title = \iconv(
            iconv_get_encoding()['input_encoding'],
            'ascii//TRANSLIT',
            \strtoupper($this->unharmonized_title)
        );
        $second_before_end = $this->get_endtime()
            ->sub(new \DateInterval('PT1S'));
        return [
            (string)$this->channel_id,
            \implode(';', $this->omroepen),
            Log::format_theoretical_date($this->get_starttime()),
            Log::format_theoretical_time($this->get_starttime()),
            $this->get_endtime()->getTimestamp() - $this->get_starttime()->getTimestamp(),
            Log::format_theoretical_time($second_before_end),
            (string)($this->prog_id ?? ''),
            $this->program_type->value,
            $unharmonized_title,
            $this->sub_title ?? '',
            isset($this->promo_type_id) ? (string)$this->promo_type_id->value : '-1',
            $this->secondary_unharmonized_title ?? '',
            $this->tertiary_unharmonized_title ?? '',
            (string)($this->promotion_channel_id ?? -1),
            (string)($this->promotion_day ?? -1),
            isset($this->repeat_code) ? (string)$this->repeat_code->value : '-1',
            $this->reconciliation_key ?? '',
            $this->program_typology ?? '',
            $this->ccc ?? '',
            $this->promo_id ?? ''
        ];
    }

    public function __toString(): string {
        return \implode("\t", $this->to_array());
    }

}
