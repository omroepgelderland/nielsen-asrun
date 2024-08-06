<?php

namespace nielsen_asrun;

enum RepeatCode: int {
    case LiveOrPrerecorded = 100;
    case Last7Days = 200;
    case Ever = 300;
}
