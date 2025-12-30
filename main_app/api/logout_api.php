<?php
require_once 'api_bootstrap.php';
session_destroy();
sendResponse(['success' => true]);
