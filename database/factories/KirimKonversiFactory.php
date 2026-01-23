<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KirimKonversi>
 */
class KirimKonversiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gclid = str_replace('-', '_', $this->faker->uuid());
        $jobid = $this->faker->numberBetween(225000000000, 225999999999);
        $date = $this->faker->dateTimeInInterval('-1 month', 'now')->format('Y-m-d H:i:sP');

        return [
            'gclid' => $gclid,
            'jobid' => $jobid,
            'waktu' => $date,
            'status' => $this->faker->randomElement(['success', 'failed']),
            'response' => json_encode([
                'gclid' => $gclid,
                'jobid' => $jobid,
                'conversionAction' => $this->faker->sentence(),
                'conversionDateTime' => $date,
            ]),
            'source' => $this->faker->randomElement(['greetingads', 'manual']),
            'rekap_form_id' => $this->faker->randomDigit(),
        ];
    }
}
