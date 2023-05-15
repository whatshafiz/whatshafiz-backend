<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Models\WhatsappMessengerNumber;
use App\Models\WhatsappMessengerNumberUser;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Tests\BaseFeatureTest;

class WhatsappMessengerNumberTest extends BaseFeatureTest
{
    protected string $uri;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->uri = self::BASE_URI . '/whatsapp-messenger-numbers';
    }

    /** @test */
    public function it_should_not_get_whatsapp_messenger_number_list_when_user_is_not_admin()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_get_active_whatsapp_messenger_numbers_list_when_user_is_admin()
    {
        $whatsappMessengerNumbers = WhatsappMessengerNumber::factory()->count(2, 5)->create();
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $response = $this->actingAs($user)->json('GET', $this->uri);

        $response->assertOk();

        foreach ($whatsappMessengerNumbers->where('is_active', true) as $activeWhatsappMessengerNumber) {
            $response->assertJsonFragment($activeWhatsappMessengerNumber->toArray());
        }

        foreach ($whatsappMessengerNumbers->where('is_active', false) as $passiveWhatsappMessengerNumber) {
            $response->assertJsonMissing($passiveWhatsappMessengerNumber->toArray(), true);
        }
    }

    /** @test */
    public function it_should_not_create_whatsapp_messenger_number_when_user_is_not_admin()
    {
        $user = User::factory()->create();
        $whatsappMessengerNumberData = WhatsappMessengerNumber::factory()->raw();

        $response = $this->actingAs($user)->json('POST', $this->uri, $whatsappMessengerNumberData);

        $response->assertForbidden();
    }

    /** @test */
    public function it_should_create_whatsapp_messenger_number_record_while_sending_only_instance_id_when_user_is_admin()
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $whatsappMessengerNumberData = WhatsappMessengerNumber::factory()->make()->only(['instance_id']);

        $response = $this->actingAs($user)->json('POST', $this->uri, $whatsappMessengerNumberData);

        $response->assertSuccessful();

        $this->assertDatabaseHas(
            'whatsapp_messenger_numbers',
            array_merge(
                $whatsappMessengerNumberData,
                ['phone_number' => null, 'is_active' => true, 'last_activity_at' => $now]
            )
        );
    }

    /** @test */
    public function it_should_update_phone_number_value_of_whatsapp_messenger_number_while_sending_phone_number_with_instance_id_when_user_is_admin()
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $whatsappMessengerNumber = WhatsappMessengerNumber::factory()->create(['phone_number' => null]);
        $whatsappMessengerNumberData = WhatsappMessengerNumber::factory()
            ->make(['instance_id' => $whatsappMessengerNumber->instance_id])
            ->only(['instance_id', 'phone_number']);

        $response = $this->actingAs($user)->json('POST', $this->uri, $whatsappMessengerNumberData);

        $response->assertSuccessful();

        $this->assertDatabaseHas(
            'whatsapp_messenger_numbers',
            array_merge(
                $whatsappMessengerNumberData,
                ['is_active' => true, 'last_activity_at' => $now]
            )
        );
    }

    /** @test */
    public function it_should_make_passive_whatsapp_messenger_number_while_sending_phone_number_with_new_instance_id_when_user_is_admin()
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $whatsappMessengerNumber = WhatsappMessengerNumber::factory()->create();
        $whatsappMessengerNumberData = WhatsappMessengerNumber::factory()
            ->make(['phone_number' => $whatsappMessengerNumber->phone_number])
            ->only(['instance_id', 'phone_number']);

        $response = $this->actingAs($user)->json('POST', $this->uri, $whatsappMessengerNumberData);

        $response->assertSuccessful();

        $this->assertDatabaseHas(
            'whatsapp_messenger_numbers',
            ['id' => $whatsappMessengerNumber->id, 'is_active' => false, 'last_activity_at' => $now]
        );
    }
}
