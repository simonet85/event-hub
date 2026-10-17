<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Le service de paiement ne peut pas créer ou vérifier un paiement pour le moment.
 */
class PaymentUnavailable extends RuntimeException {}
