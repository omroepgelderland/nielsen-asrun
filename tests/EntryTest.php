<?php

declare(strict_types=1);

namespace nielsen_asrun;

use PHPUnit\Framework\TestCase;

final class EntryTest extends TestCase {

    public function testProgramEntryWithoutProgID(): void {
        $this->expectException(ASRunException::class);
        $entry = Entry::create_program_entry([
            'channel_id' => 1,
            'omroepen' => ['o'],
            'starttime' => new \DateTime(),
            'endtime' => new \DateTime(),
            'unharmonized_title' => 't',
            'repeat_code' => RepeatCode::Ever
        ]);
    }
    
    public function testProgramEntryWithoutRepeatCode(): void {
        $this->expectException(ASRunException::class);
        $entry = Entry::create_program_entry([
            'channel_id' => 1,
            'omroepen' => ['o'],
            'starttime' => new \DateTime(),
            'endtime' => new \DateTime(),
            'unharmonized_title' => 't',
            'prog_id' => '6'
        ]);
    }
    
    public function testPromoEntryWithoutPromoTypeID(): void {
        $this->expectException(ASRunException::class);
        $entry = Entry::create_promo_entry([
            'channel_id' => 1,
            'omroepen' => ['o'],
            'starttime' => new \DateTime(),
            'endtime' => new \DateTime(),
            'unharmonized_title' => 't'
        ]);
    }

    public function testProgram(): void {
        $entry = Entry::create_program_entry([
            'channel_id' => 234,
            'omroepen' => ['OB'],
            'starttime' => new \DateTime('2024-06-17 02:00:00'),
            'endtime' => new \DateTime('2024-06-17 02:03:30'),
            'prog_id' => '4453719',
            'unharmonized_title' => 'KRAAK.',
            'repeat_code' => RepeatCode::Last7Days
        ]);
        $this->assertEquals(
            "234\tOB\t20240617\t020000\t210\t020329\t4453719\tPROGRAMMA\tKRAAK.\t\t-1\t\t\t-1\t-1\t200\t\t\t\t",
            $entry->__toString()
        );
    }

    public function testPromo(): void {
        $entry = Entry::create_promo_entry([
            'channel_id' => 234,
            'omroepen' => ['OB'],
            'starttime' => new \DateTime('2024-06-17 04:29:50'),
            'endtime' => new \DateTime('2024-06-17 04:30:32'),
            'unharmonized_title' => 'PRBMDI DE BRABANTSE KANT VAN DE MEDAILLE DINSD',
            'promo_type_id' => PromoType::Promo,
            'promo_id' => '4450538'
        ]);
        $this->assertEquals(
            "234\tOB\t20240617\t042950\t42\t043031\t\tPROMO\tPRBMDI DE BRABANTSE KANT VAN DE MEDAILLE DINSD\t\t99\t\t\t-1\t-1\t-1\t\t\t\t4450538",
            $entry->__toString()
        );
    }

    public function testStationID(): void {
        $entry = Entry::create_station_id_entry([
            'channel_id' => 234,
            'omroepen' => ['OB'],
            'starttime' => new \DateTime('2024-06-17 07:04:53'),
            'endtime' => new \DateTime('2024-06-17 07:05:01'),
            'unharmonized_title' => 'SID 206 BOSFIETS - 2024-03-25 - 2024-07-01',
            'promo_id' => '4435714'
        ]);
        $this->assertEquals(
            "234\tOB\t20240617\t070453\t8\t070500\t\tSTATIONID\tSID 206 BOSFIETS - 2024-03-25 - 2024-07-01\t\t-1\t\t\t-1\t-1\t-1\t\t\t\t4435714",
            $entry->__toString()
        );
    }

    public function testBreak(): void {
        $entry = Entry::create_break_entry([
            'channel_id' => 234,
            'omroepen' => ['OB'],
            'starttime' => new \DateTime('2024-06-17 06:57:04'),
            'endtime' => new \DateTime('2024-06-17 06:59:55'),
            'unharmonized_title' => '064'
        ]);
        $this->assertEquals(
            "234\tOB\t20240617\t065704\t171\t065954\t\tBREAK\t064\t\t-1\t\t\t-1\t-1\t-1\t\t\t\t",
            $entry->__toString()
        );
    }

    public function testEndTimeBeforeStartTime(): void {
        $this->expectException(EndTimeBeforeStart::class);
        Entry::create_promo_entry([
            'channel_id' => 193,
            'omroepen' => ['OG'],
            'starttime' => new \DateTime('2024-06-17 16:53:59'),
            'endtime' => new \DateTime('2024-06-17 16:53:58'),
            'unharmonized_title' => '(PROMO_TEASER) GLD NIEUWS',
            'promo_type_id' => PromoType::Promo
        ]);
    }
    
    // public function testProgramEntryIncomplete(): void {
    //     $this->expectException(\TypeError::class);
    //     $entry = Entry::create_program_entry([
    //         'prog_id' => '5',
    //         'repeat_code' => RepeatCode::Ever
    //     ]);
    // }

}
