<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Le montant demandé dépasse ce qu'il reste à financer pour un cadeau.
 */
class GiftBudgetExceeded extends RuntimeException {}
