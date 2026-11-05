<?php
declare(strict_types=1);

namespace App\Domain\Booking;

use DateTimeImmutable;

final class BookingPolicy
{
    public const OPENING_HOURS = ['09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00'];

    public function __construct(
        private int $hourlyPrice,
        private int $depositPercent,
    ) {
        if ($hourlyPrice < 1 || $depositPercent < 1 || $depositPercent > 100) {
            throw new \InvalidArgumentException('La tarifa y el porcentaje de seña deben ser válidos.');
        }
    }

    public function quote(int $duration): array
    {
        $duration = max(1, min(8, $duration));
        $total = $this->hourlyPrice * $duration;
        return [
            'price_per_hour' => $this->hourlyPrice,
            'duration' => $duration,
            'total' => $total,
            'deposit' => (int) round($total * $this->depositPercent / 100),
        ];
    }

    public function maximumBookingDate(?DateTimeImmutable $today = null): DateTimeImmutable
    {
        $today ??= new DateTimeImmutable('today');
        $targetMonth = $today->modify('first day of this month')->modify('+3 months');
        return $targetMonth->setDate(
            (int) $targetMonth->format('Y'),
            (int) $targetMonth->format('m'),
            min((int) $today->format('j'), (int) $targetMonth->format('t')),
        );
    }

    public function validStart(string $date, string $time, int $duration, ?DateTimeImmutable $now = null): ?DateTimeImmutable
    {
        if ($duration < 1 || $duration > 8) {
            return null;
        }

        $start = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time);
        $errors = DateTimeImmutable::getLastErrors();
        if (
            !$start
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $start->format('Y-m-d H:i') !== $date . ' ' . $time
        ) {
            return null;
        }

        $now ??= new DateTimeImmutable('now');
        $today = $now->setTime(0, 0);
        if ($start->setTime(0, 0) < $today || $start->setTime(0, 0) > $this->maximumBookingDate($today)) {
            return null;
        }

        for ($offset = 0; $offset < $duration; $offset++) {
            if (!in_array($start->modify("+{$offset} hours")->format('H:i'), self::OPENING_HOURS, true)) {
                return null;
            }
        }

        $end = $start->modify("+{$duration} hours");
        if ($start <= $now || $end > $start->setTime(22, 0)) {
            return null;
        }

        return $start;
    }
}
