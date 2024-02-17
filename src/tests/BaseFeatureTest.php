<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;

class BaseFeatureTest extends TestCase
{
    // use DatabaseTransactions;
    use WithFaker;

    const BASE_URI = '/api/v1';
}
