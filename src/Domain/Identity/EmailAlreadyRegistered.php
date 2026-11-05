<?php
declare(strict_types=1);

namespace App\Domain\Identity;

use DomainException;

final class EmailAlreadyRegistered extends DomainException {}
