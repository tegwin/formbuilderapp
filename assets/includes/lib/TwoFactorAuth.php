<?php
namespace RobThree\Auth;

class TwoFactorAuth
{
    protected $issuer;
    protected $digits;
    protected $period;
    protected $algorithm;

    public function __construct($issuer = null, $digits = 6, $period = 30, $algorithm = 'sha1')
    {
        $this->issuer = $issuer;
        $this->digits = $digits;
        $this->period = $period;
        $this->algorithm = $algorithm;
    }

    public function createSecret($bits = 160)
    {
        $bytes = ceil($bits / 8);
        $random = random_bytes($bytes);
        return $this->base32Encode($random);
    }

    public function getQRCodeImageUrl($label, $secret, $size = 200)
    {
        $url = $this->getQRText($label, $secret);
        return 'https://chart.googleapis.com/chart?chs=' . $size . 'x' . $size . '&cht=qr&chl=' . urlencode($url);
    }

    public function getQRText($label, $secret)
    {
        $issuer = rawurlencode($this->issuer);
        $labelEncoded = rawurlencode($label);
        $params = [
            'secret' => $secret,
            'issuer' => $this->issuer,
            'algorithm' => strtoupper($this->algorithm),
            'digits' => $this->digits,
            'period' => $this->period
        ];
        return "otpauth://totp/{$issuer}:{$labelEncoded}?" . http_build_query($params);
    }
    public function verifyCode($secret, $code, $discrepancy = 1, $timestamp = null)
    {
        $timestamp = $timestamp ?? time();
        $secretKey = $this->base32Decode($secret);

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = $this->oathHotp($secretKey, floor($timestamp / $this->period) + $i);
            if ($calculatedCode === $code) {
                return true;
            }
        }

        return false;
    }

    private function oathHotp($key, $counter)
    {
        $binCounter = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac($this->algorithm, $binCounter, $key, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $binary = (ord($hash[$offset]) & 0x7F) << 24 |
                  (ord($hash[$offset + 1]) & 0xFF) << 16 |
                  (ord($hash[$offset + 2]) & 0xFF) << 8 |
                  (ord($hash[$offset + 3]) & 0xFF);
        return str_pad($binary % pow(10, $this->digits), $this->digits, '0', STR_PAD_LEFT);
    }

    private function base32Encode($data)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binaryString = '';
        foreach (str_split($data) as $char) {
            $binaryString .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        $fiveBitChunks = str_split($binaryString, 5);
        $base32 = '';
        foreach ($fiveBitChunks as $chunk) {
            $base32 .= $alphabet[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }
        return rtrim(chunk_split($base32, 4, ' '));
    }

    private function base32Decode($b32)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $b32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $b32));
        $binaryString = '';
        foreach (str_split($b32) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos === false) continue;
            $binaryString .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $eightBitChunks = str_split($binaryString, 8);
        $decoded = '';
        foreach ($eightBitChunks as $chunk) {
            if (strlen($chunk) < 8) continue;
            $decoded .= chr(bindec($chunk));
        }
        return $decoded;
    }
}
