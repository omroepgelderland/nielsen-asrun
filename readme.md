# Formatter for ‘As Run Log’ and ‘Program Before’ files for Nielsen Hybrid Audit

Generates the ‘program before’ files which are sent before the broadcast day and the ‘as run logs’ which are sent after the end of the broadcast day. See the docs directory for the format specification and use.

## Installation

This library requires PHP8.1.0 or higher. The recommended way to install is through Composer:

```bash
composer require omroepgelderland/nielsen-asrun
```

## Usage example

```php
use nielsen_asrun\Entry;
use nielsen_asrun\Log;
use nielsen_asrun\PromoType;
use nielsen_asrun\RepeatCode;
use nielsen_asrun\TypologySource;

$log = Log::create_asrunlog([
    'typology_source' => TypologySource::None,
    'encoding' => 'iso-8859-1',
    'broadcast_day' => new \DateTime('2024-06-17'),
    'author' => 'Omroep Brabant',
    'channel_name' => 'OB',
    'channel_abbreviation' => 'tvbrab'
]);

// Add a program entry
$log->add_entry(Entry::create_program_entry([
    'channel_id' => 234,
    'omroepen' => ['OB'],
    'starttime' => new \DateTime('2024-06-17 02:00:00'),
    'endtime' => new \DateTime('2024-06-17 02:03:29'),
    'prog_id' => '4453719',
    'unharmonized_title' => 'KRAAK.',
    'repeat_code' => RepeatCode::Last7Days
]));

// Add a break entry
$log->add_entry(Entry::create_break_entry([
    'channel_id' => 234,
    'omroepen' => ['OB'],
    'starttime' => new \DateTime('2024-06-17 06:57:04'),
    'endtime' => new \DateTime('2024-06-17 06:59:54'),
    'unharmonized_title' => '64'
]));

// Add a promo entry
$log->add_entry(Entry::create_promo_entry([
    'channel_id' => 234,
    'omroepen' => ['OB'],
    'starttime' => new \DateTime('2024-06-17 02:03:30'),
    'endtime' => new \DateTime('2024-06-17 02:03:34'),
    'unharmonized_title' => 'BÜMPAT BUMPER AFL. TERUGKIJKEN BRABANT+ - 2023',
    'promo_type_id' => PromoType::Promo,
    'promo_id' => '4365342'
]));

// Add a station id entry
$log->add_entry(Entry::create_station_id_entry([
    'channel_id' => 234,
    'omroepen' => ['OB'],
    'starttime' => new \DateTime('2024-06-17 07:04:53'),
    'endtime' => new \DateTime('2024-06-17 07:05:00'),
    'unharmonized_title' => 'SID 206 BOSFIETS - 2024-03-25 - 2024-07-01',
    'promo_id' => '4435714'
]));

// Merge consecutive breaks
$log->merge_breaks();

// Save to file
\file_put_contents($log->get_filename(), $log);
```
