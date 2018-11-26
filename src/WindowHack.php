<?php
namespace SVBX;

class WindowHack {
    static public function goBack(string $alertMsg) {
        echo "
        <script src='/js/window_hack.js?v=1543264421231'></script>
        <script>WindowHack.goBack('$alertMsg')</script>";
    }
}
