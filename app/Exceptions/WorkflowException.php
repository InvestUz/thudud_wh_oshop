<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Pipeline qoidalari buzilganda (ruxsat etilmagan o'tish, ruxsat yo'qligi) tashlanadi.
 */
class WorkflowException extends RuntimeException
{
}
