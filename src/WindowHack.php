<?php
namespace SVBX;

class WindowHack {
    static public function goBack(string $alertMsg = null) {
        echo "
        <script src='/js/window_hack.js?v=1543276175810'></script>
        <script>WindowHack.goBack('$alertMsg')</script>";
    }
}
