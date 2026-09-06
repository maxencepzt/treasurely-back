<?php

namespace App\Service\Designer\Exception;

/**
 * Modification refusée parce qu'elle détruirait de la progression de joueurs ou
 * figerait des résultats déjà publiés. Le message est destiné au concepteur.
 */
final class HuntConflictException extends \DomainException
{
}
