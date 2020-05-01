<?php
register_hook("twig_init", function() {
  global $twig;

  $twig->addFilter(new Twig_SimpleFilter('age', function($date) {
    if ($date == null) {
      return '';
    }

    $now = new DateTime();
    $date1 = new DateTime($date);
    $diff = $now->getTimestamp() - $date1->getTimestamp();

    if ($diff < 0) {
      $text = "not yet";
    }
    else if ($diff < 2 * 60) {
      $text = "just now";
    }
    elseif ($diff < 45 * 60) {
      $text = round($diff / 60) . " minutes ago";
    }
    elseif ($diff < 90 * 60) {
      $text = "an hour ago";
    }
    elseif ($diff < 24 * 3600) {
      $text = round($diff / 3600) . " hours ago";
    }
    elseif ($diff < 48 * 3600) {
      $text = "yesterday";
    }
    elseif ($diff < 61 * 86400) {
      $text = round($diff / 86400) . " days ago";
    }
    elseif ($diff < 380 * 86400) {
      $text = round($diff / 30.4 / 86400) . " months ago";
    }
    else {
      $text = round($diff / 365.25 / 86400) . " years ago";
    }

    return "<span class=\"age\" value=\"" . htmlspecialchars($date) . "\" title=\"" . htmlspecialchars($date) . "\">{$text}</span>";
  }, array("is_safe"=>array("html"))));
});
