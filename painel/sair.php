<?php
declare(strict_types=1);
require __DIR__ . '/../api/lib.php';
lmc_sessao();
$_SESSION = [];
session_destroy();
header('Location: index.php');
