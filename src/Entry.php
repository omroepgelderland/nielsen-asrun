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
        [
            'channel_id' => $this->channel_id,
            'omroepen' => $this->omroepen,
            'starttime' => $this->starttime,
            'endtime' => $this->endtime,
            'prog_id' => $this->prog_id,
            'program_type' => $this->program_type,
            'unharmonized_title' => $this->unharmonized_title,
            'sub_title' => $this->sub_title,
            'promo_type_id' => $this->promo_type_id,
            'secondary_unharmonized_title' => $this->secondary_unharmonized_title,
            'tertiary_unharmonized_title' => $this->tertiary_unharmonized_title,
            'promotion_channel_id' => $this->promotion_channel_id,
            'promotion_day' => $this->promotion_day,
            'repeat_code' => $this->repeat_code,
            'reconciliation_key' => $this->reconciliation_key,
            'program_typology' => $this->program_typology,
            'ccc' => $this->ccc,
            'promo_id' => $this->promo_id
        ] = $data;
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
        return [
            (string)$this->channel_id,
            \implode(';', $this->omroepen),
            Log::format_theoretical_date($this->get_starttime()),
            Log::format_theoretical_time($this->get_starttime()),
            $this->get_endtime()->getTimestamp() - $this->get_starttime()->getTimestamp(),
            Log::format_theoretical_time($this->get_endtime()),
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
