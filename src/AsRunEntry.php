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
class AsRunEntry {

    private int $channel_id;
    /** @var list<string> */
    private array $omroepen;
    public \DateTime $starttime;
    public \DateTime $endtime;
    private ?string $prog_id;
    public ProgramType $program_type;
    private string $unharmonized_title;
    private ?string $sub_title;
    private ?PromoType $promo_type_id;
    private ?string $secondary_unharmonized_title;
    private ?string $tertiary_unharmonized_title;
    private ?int $promotion_channel_id;
    private ?int $promotion_day;
    private ?RepeatCode $repeat_code;
    private ?string $reconciliation_key;
    private ?string $program_typology;
    private ?string $ccc;
    private ?string $promo_id;

    /**
     * @param Params $data
     */
    private function __construct( $data ) {
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

    /**
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
            throw new ASRunlogException(
                'Field prod_id is mandatory for ProgramType Programma'
            );
        }
        /** @phpstan-ignore isset.offset */
        if ( !isset($data['repeat_code']) ) {
            throw new ASRunlogException(
                'Field repeat_code is mandatory for ProgramType Programma'
            );
        }
        return new self([
            'program_type' => ProgramType::Programma,
            ...$data
        ]);
    }

    /**
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
            throw new ASRunlogException(
                'Field promo_type_id is mandatory for ProgramType Promo'
            );
        }
        return new self([
            'program_type' => ProgramType::Promo,
            ...$data
        ]);
    }

    /**
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
            Log::format_theoretical_date($this->starttime),
            Log::format_theoretical_time($this->starttime),
            $this->endtime->getTimestamp() - $this->starttime->getTimestamp(),
            Log::format_theoretical_time($this->endtime),
            (string)$this->prog_id,
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
