<?php
namespace SVBX;

class WindowHack {
    public function goBack(string $alertMsg) {
        echo "
        <script src='/js/window_hack.js'></script>
        <script>
            (new WindowHack()).goBack('$alertMsg')
        </script>";
    }
}
