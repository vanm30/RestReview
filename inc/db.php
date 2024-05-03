<?php
  $db = new PDO($_ENV('DB'));
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);