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

    /** @var Params */
    private array $data;
    public ProgramType $program_type;

    /**
     * @param Params $data
     */
    private function __construct( array $data ) {
        $this->data = $data;
        $this->program_type = $data['program_type'];
    }

    public function get_starttime(): \DateTime {
        return clone $this->data['starttime'];
    }

    public function get_endtime(): \DateTime {
        return clone $this->data['endtime'];
    }

    public function set_starttime( \DateTime $dt ): void {
        $this->data['starttime'] = clone $dt;
    }

    public function set_endtime( \DateTime $dt ): void {
        $this->data['endtime'] = clone $dt;
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
                'Field prod_id is mandatory for ProgramType Programma'
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
            \strtoupper($this->data['unharmonized_title'])
        );
        return [
            (string)$this->data['channel_id'],
            \implode(';', $this->data['omroepen']),
            Log::format_theoretical_date($this->get_starttime()),
            Log::format_theoretical_time($this->get_starttime()),
            $this->get_endtime()->getTimestamp() - $this->get_starttime()->getTimestamp(),
            Log::format_theoretical_time($this->get_endtime()),
            (string)$this->data['prog_id'],
            $this->program_type->value,
            $unharmonized_title,
            $this->data['sub_title'] ?? '',
            isset($this->data['promo_type_id']) ? (string)$this->data['promo_type_id']->value : '-1',
            $this->data['secondary_unharmonized_title'] ?? '',
            $this->data['tertiary_unharmonized_title'] ?? '',
            (string)($this->data['promotion_channel_id'] ?? -1),
            (string)($this->data['promotion_day'] ?? -1),
            isset($this->data['repeat_code']) ? (string)$this->data['repeat_code']->value : '-1',
            $this->data['reconciliation_key'] ?? '',
            $this->data['program_typology'] ?? '',
            $this->data['ccc'] ?? '',
            $this->data['promo_id'] ?? ''
        ];
    }

    public function __toString(): string {
        return \implode("\t", $this->to_array());
    }

}
