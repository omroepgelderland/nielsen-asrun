<?php

declare(strict_types=1);

namespace nielsen_asrun;

use PHPUnit\Framework\TestCase;

final class LogTest extends TestCase {

    private function getTestLog(): Log {
        return Log::create_asrunlog([
            'typology_source' => TypologySource::None,
            'encoding' => 'iso-8859-1',
            'broadcast_day' => new \DateTime('2024-06-17'),
            'author' => 'Omroep Brabant',
            'channel_name' => 'OB',
            'channel_abbreviation' => 'tvbrab',
            'created' => new \DateTime('2024-06-18 12:56:18')
        ]);
    }

    private function getTestProgramBefore(): Log {
        return Log::create_program_before([
            'typology_source' => TypologySource::None,
            'encoding' => 'iso-8859-1',
            'broadcast_day' => new \DateTime('2024-06-17'),
            'author' => 'Omroep Brabant',
            'channel_name' => 'OB',
            'channel_abbreviation' => 'tvbrab',
            'created' => new \DateTime('2024-06-18 13:10:00')
        ]);
    }

    private function getExpectedLogHeader(): string {
        return <<<EOT
        %FORMAT\t20230101\tBROADCASTDAY\t20240617\tCREATED\t20240618\tAT\t125618\tBY\tOB    \r
        %COLLATING_SEQUENCE\tISO-8859-1\r
        %AUTHOR\tOmroep Brabant\r
        %FILETYPE\tAsRun\r
        %OTHERFIELDS\t\r
        %PIVOTHOUR\t020000\r
        %TYPOLOGYSOURCE\tNONE\r
        %COMMENT ChannelID\tOmroep\tDate\tStartTime\tDuration\tEndTime\tProgID\tProgramType\tUnharmonizedTitle\tSubTitle\tPromoTypeID\tSecondaryUnharmonizedTitle\tTertiaryUnharmonizedTitle\tPromotionChannelID\tPromotionDay\tRepeatCode\tReconciliationKey\tProgramTypology\tCCC\tPromoID\r
        EOT;
    }

    public function testLog(): void {
        $log = $this->getTestLog();
        $log->add_entry(Entry::create_program_entry([
            'channel_id' => 234,
            'omroepen' => ['OB'],
            'starttime' => new \DateTime('2024-06-17 02:00:00'),
            'endtime' => new \DateTime('2024-06-17 02:03:30'),
            'prog_id' => '4453719',
            'unharmonized_title' => 'KRAAK.',
            'repeat_code' => RepeatCode::Last7Days
        ]));

        $expected = <<<EOT
        {$this->getExpectedLogHeader()}
        234\tOB\t20240617\t020000\t210\t020329\t4453719\tPROGRAMMA\tKRAAK.\t\t-1\t\t\t-1\t-1\t200\t\t\t\t\r

        EOT;

        $this->assertEquals($expected, $log->__toString());
    }

    /**
     * Entry2 starts between entry1 start and entry1 end.
     */
    public function testOverlap(): void {
        $log = $this->getTestLog();

        $log->add_entry(Entry::create_program_entry([
            'channel_id' => 234,
            'omroepen' => ['OB'],
            'starttime' => new \DateTime('2024-06-17 02:00:00'),
            'endtime' => new \DateTime('2024-06-17 02:03:32'),
            'prog_id' => '4453719',
            'unharmonized_title' => 'KRAAK.',
            'repeat_code' => RepeatCode::Last7Days
        ]));
        $log->add_entry(Entry::create_promo_entry([
            'channel_id' => 234,
            'omroepen' => ['OB'],
            'starttime' => new \DateTime('2024-06-17 02:03:30'),
            'endtime' => new \DateTime('2024-06-17 02:03:35'),
            'promo_id' => '4365342',
            'unharmonized_title' => 'BUMPAT BUMPER AFL. TERUGKIJKEN BRABANT+ - 2023',
            'promo_type_id' => PromoType::Promo
        ]));

        $expected = <<<EOT
        {$this->getExpectedLogHeader()}
        234\tOB\t20240617\t020000\t210\t020329\t4453719\tPROGRAMMA\tKRAAK.\t\t-1\t\t\t-1\t-1\t200\t\t\t\t\r
        234\tOB\t20240617\t020330\t5\t020334\t\tPROMO\tBUMPAT BUMPER AFL. TERUGKIJKEN BRABANT+ - 2023\t\t99\t\t\t-1\t-1\t-1\t\t\t\t4365342\r

        EOT;

        $this->assertEquals($expected, $log->__toString());
    }

    public function testProgramBefore(): void {
        $log = $this->getTestProgramBefore();

        $log->add_entry(Entry::create_program_entry([
            'channel_id' => 234,
            'omroepen' => ['OB'],
            'starttime' => new \DateTime('2024-06-17 02:00:00'),
            'endtime' => new \DateTime('2024-06-17 07:00:00'),
            'prog_id' => '4458035',
            'unharmonized_title' => 'NACHTPROGRAMMERING',
            'repeat_code' => RepeatCode::Ever
        ]));

        $expected = <<<EOT
        %FORMAT\t20230101\tBROADCASTDAY\t20240617\tCREATED\t20240618\tAT\t131000\tCHANNEL\tOB    \r
        %COLLATING_SEQUENCE\tISO-8859-1\r
        %AUTHOR\tOmroep Brabant\r
        %FILETYPE\tBEFORE_BROADCAST\r
        %OTHERFIELDS\t\r
        %PIVOTHOUR\t020000\r
        %TYPOLOGYSOURCE\tNONE\r
        %COMMENT ChannelID\tOmroep\tDate\tBeginTime\tDuration\tEndTime\tProgID\tProgramType\tUnharmonizedTitle\tSubTitle\tPromoTypeID\tSecondaryUnharmonizedTitle\tTertiaryUnharmonizedTitle\tPromotionChannelID\tPromotionDay\tRepeatCode\tReconciliationKey\tProgramTypology\tCCC\tPromoID\r
        234\tOB\t20240617\t020000\t18000\t065959\t4458035\tPROGRAMMA\tNACHTPROGRAMMERING\t\t-1\t\t\t-1\t-1\t300\t\t\t\t\r

        EOT;

        $this->assertEquals($expected, $log->__toString());
    }

    public function testTheoreticalDate(): void {
        $this->assertEquals(
            '20240617',
            Log::format_theoretical_date(new \DateTime('2024-06-17 02:00:00'))
        );
        $this->assertEquals(
            '20240617',
            Log::format_theoretical_date(new \DateTime('2024-06-18 00:00:00'))
        );
        $this->assertEquals(
            '20240617',
            Log::format_theoretical_date(new \DateTime('2024-06-18 01:59:59'))
        );
    }
    
    public function testTheoreticalTime(): void {
        $this->assertEquals(
            '020000',
            Log::format_theoretical_time(new \DateTime('2024-06-17 02:00:00'))
        );
        $this->assertEquals(
            '240000',
            Log::format_theoretical_time(new \DateTime('2024-06-18 00:00:00'))
        );
        $this->assertEquals(
            '255959',
            Log::format_theoretical_time(new \DateTime('2024-06-18 01:59:59'))
        );
    }
    
    // public function testAddEntry(): void {}
    
    public function testFillGaps(): void {
        $log = $this->getTestLog();

        $log->add_entry(Entry::create_program_entry([
            'channel_id' => 234,
            'omroepen' => ['OB'],
            'starttime' => new \DateTime('2024-06-17 02:00:00'),
            'endtime' => new \DateTime('2024-06-17 02:03:30'),
            'prog_id' => '4453719',
            'unharmonized_title' => 'KRAAK.',
            'repeat_code' => RepeatCode::Last7Days
        ]));
        $log->add_entry(Entry::create_promo_entry([
            'channel_id' => 234,
            'omroepen' => ['OB'],
            'starttime' => new \DateTime('2024-06-17 02:03:35'),
            'endtime' => new \DateTime('2024-06-17 02:04:17'),
            'promo_id' => '4450538',
            'unharmonized_title' => 'PRBMDI DE BRABANTSE KANT VAN DE MEDAILLE DINSD',
            'promo_type_id' => PromoType::Promo
        ]));
        $log->fill_gaps();

        $expected = <<<EOT
        {$this->getExpectedLogHeader()}
        234\tOB\t20240617\t020000\t215\t020334\t4453719\tPROGRAMMA\tKRAAK.\t\t-1\t\t\t-1\t-1\t200\t\t\t\t\r
        234\tOB\t20240617\t020335\t86185\t255959\t\tPROMO\tPRBMDI DE BRABANTSE KANT VAN DE MEDAILLE DINSD\t\t99\t\t\t-1\t-1\t-1\t\t\t\t4450538\r

        EOT;

        $this->assertEquals($expected, $log->__toString());
    }
    
    public function testGetFilename(): void {
        $log = $this->getTestLog();
        $this->assertEquals('AR20240617.tvbrab', $log->get_filename());

        $pb = $this->getTestProgramBefore();
        $this->assertEquals('BR20240617.tvbrab', $pb->get_filename());
    }
    
    public function testMergeBreaks(): void {
        $log = $this->getTestLog();

        $log->add_entry(Entry::create_break_entry([
            'channel_id' => 234,
            'omroepen' => ['OB'],
            'starttime' => new \DateTime('2024-06-17 06:57:04'),
            'endtime' => new \DateTime('2024-06-17 06:59:55'),
            'unharmonized_title' => '064'
        ]));
        $log->add_entry(Entry::create_break_entry([
            'channel_id' => 234,
            'omroepen' => ['OB'],
            'starttime' => new \DateTime('2024-06-17 07:00:00'),
            'endtime' => new \DateTime('2024-06-17 07:01:00'),
            'unharmonized_title' => '065'
        ]));

        $log->merge_breaks();

        $expected = <<<EOT
        {$this->getExpectedLogHeader()}
        234\tOB\t20240617\t065704\t236\t070059\t\tBREAK\t064\t\t-1\t\t\t-1\t-1\t-1\t\t\t\t\r

        EOT;

        $this->assertEquals($expected, $log->__toString());
    }
    
    public function testMergePrograms(): void {
        $log = $this->getTestLog();
        $log->add_entry(Entry::create_program_entry([
            'channel_id' => 234,
            'omroepen' => ['OB'],
            'starttime' => new \DateTime('2024-06-17 02:00:00'),
            'endtime' => new \DateTime('2024-06-17 02:03:30'),
            'prog_id' => '4453719',
            'unharmonized_title' => 'KRAAK.',
            'repeat_code' => RepeatCode::Last7Days
        ]));
        $log->add_entry(Entry::create_program_entry([
            'channel_id' => 234,
            'omroepen' => ['OB'],
            'starttime' => new \DateTime('2024-06-17 02:23:58'),
            'endtime' => new \DateTime('2024-06-17 03:01:56'),
            'prog_id' => '4453719',
            'unharmonized_title' => 'KRAAK.',
            'repeat_code' => RepeatCode::Last7Days
        ]));
        $log->add_entry(Entry::create_program_entry([
            'channel_id' => 234,
            'omroepen' => ['OB'],
            'starttime' => new \DateTime('2024-06-17 03:41:07'),
            'endtime' => new \DateTime('2024-06-17 03:49:59'),
            'prog_id' => '4453720',
            'unharmonized_title' => 'BRABANT NIEUWS',
            'repeat_code' => RepeatCode::Last7Days
        ]));
        $log->merge_programs();

        $expected = <<<EOT
        {$this->getExpectedLogHeader()}
        234\tOB\t20240617\t020000\t3716\t030155\t4453719\tPROGRAMMA\tKRAAK.\t\t-1\t\t\t-1\t-1\t200\t\t\t\t\r
        234\tOB\t20240617\t034107\t532\t034958\t4453720\tPROGRAMMA\tBRABANT NIEUWS\t\t-1\t\t\t-1\t-1\t200\t\t\t\t\r

        EOT;

        $this->assertEquals($expected, $log->__toString());
    }
    
}
