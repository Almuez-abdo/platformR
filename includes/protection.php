<?php
class Protection {
    
    // منع تصوير الشاشة باستخدام JavaScript و CSS
    public static function getAntiScreenCaptureJS() {
        return '
        <script>
            // منع النقر الأيمن
            document.addEventListener("contextmenu", function(e) {
                e.preventDefault();
                return false;
            });
            
            // منع اختصارات لوحة المفاتيح للنسخ والطباعة
            document.addEventListener("keydown", function(e) {
                // Ctrl+P (Print)
                if(e.ctrlKey && e.keyCode === 80) {
                    e.preventDefault();
                    return false;
                }
                // Ctrl+S (Save)
                if(e.ctrlKey && e.keyCode === 83) {
                    e.preventDefault();
                    return false;
                }
                // Ctrl+Shift+I (Developer Tools)
                if(e.ctrlKey && e.shiftKey && e.keyCode === 73) {
                    e.preventDefault();
                    return false;
                }
                // F12 (Developer Tools)
                if(e.keyCode === 123) {
                    e.preventDefault();
                    return false;
                }
            });
            
            // كشف محاولة فتح أدوات المطور
            setInterval(function() {
                const before = new Date().getTime();
                debugger;
                const after = new Date().getTime();
                if(after - before > 100) {
                    window.location.href = "/warning.php";
                }
            }, 1000);
        </script>
        ';
    }
    
    // ترميز محتوى الفيديو
    public static function encryptVideo($inputFile, $outputFile) {
        // استخدام خوارزمية AES للتشفير
        $key = openssl_random_pseudo_bytes(32); // مفتاح 256-bit
        $iv = openssl_random_pseudo_bytes(16); // متجه بدء 128-bit
        
        $inputData = file_get_contents($inputFile);
        $encrypted = openssl_encrypt($inputData, 'aes-256-cbc', $key, 0, $iv);
        
        // حفظ الملف المشفر مع الـ IV
        file_put_contents($outputFile, $iv . $encrypted);
        
        return $key; // يجب تخزين المفتاح بشكل آمن
    }
    
    // فك تشفير الفيديو عند التشغيل
    public static function streamEncryptedVideo($encryptedFile, $userKey) {
        $data = file_get_contents($encryptedFile);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $userKey, 0, $iv);
        
        if($decrypted) {
            header('Content-Type: video/mp4');
            echo $decrypted;
        } else {
            http_response_code(403);
            echo "Unauthorized access";
        }
    }
    
    // حماية بواسطة واترمارك
    public static function addWatermark($text) {
        return '
        <style>
            .watermark {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                pointer-events: none;
                z-index: 9999;
                opacity: 0.1;
                background: repeating-linear-gradient(
                    45deg,
                    transparent,
                    transparent 10px,
                    rgba(0,0,0,0.1) 10px,
                    rgba(0,0,0,0.1) 20px
                );
            }
            .watermark::before {
                content: "' . $text . '";
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) rotate(-45deg);
                font-size: 48px;
                color: rgba(0,0,0,0.3);
                font-weight: bold;
            }
        </style>
        <div class="watermark"></div>
        ';
    }
}
?>