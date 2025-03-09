<?php
/**
 * Single-file QR Code generator (Version 1, ECC Level L, Byte mode).
 * 
 * Modification: instead of "\n", we output "|".
 */

function AFGetQR(string $data): string {

    // Nested helpers
    function computeRS(array $dataBlock, int $eccCount): array {
        static $log = [], $alog = [], $initialized = false;
        if(!$initialized){
            $initialized = true;
            $log[0] = -1;
            $val = 1;
            for($i=0; $i<255; $i++){
                $alog[$i] = $val;
                $log[$val] = $i;
                $val <<= 1;
                if($val & 0x100){
                    $val ^= 0x11d; // x^8 + x^4 + x^3 + x^2 + 1
                }
            }
        }
        // Generator polynomial for version 1-L => 7 ECC
        $ecc = array_fill(0, $eccCount, 0);
        // Precomputed generator for V1-L: [87,229,146,149,238,102,21]
        $gen = [87,229,146,149,238,102,21];

        foreach($dataBlock as $db){
            $lead = $db ^ $ecc[0];
            array_shift($ecc);
            $ecc[] = 0;
            if($lead !== 0){
                foreach($gen as $i => $coef){
                    $ecc[$i] ^= gfMul($lead, $coef, $log, $alog);
                }
            }
        }
        return $ecc;
    }

    function gfMul(int $x, int $y, array $log, array $alog): int {
        if($x===0||$y===0)return 0;
        $r = $log[$x] + $log[$y];
        return $alog[$r%255];
    }

    function placeFinderPatterns(array &$matrix): void {
        $size = count($matrix);
        foreach([[0,0],[0,$size-7],[$size-7,0]] as [$r,$c]){
            for($y=0;$y<7;$y++){
                for($x=0;$x<7;$x++){
                    $border = ($x===0||$x===6||$y===0||$y===6);
                    $center = ($x>=2&&$x<=4&&$y>=2&&$y<=4);
                    $matrix[$r+$y][$c+$x] = ($border||$center);
                }
            }
        }
    }

    function placeTimingPatterns(array &$matrix): void {
        $s = count($matrix);
        for($i=8;$i<$s-8;$i++){
            if($matrix[6][$i]===null)$matrix[6][$i]=($i%2===0);
            if($matrix[$i][6]===null)$matrix[$i][6]=($i%2===0);
        }
    }

    function isInFinder(int $x, int $y, int $size): bool {
        // Finder areas: top-left, top-right, bottom-left (7x7)
        if($x<7 && $y<7)return true;
        if($x>=$size-7 && $y<7)return true;
        if($x<7 && $y>=$size-7)return true;
        return false;
    }

    function placeDataBits(array &$matrix, string $bitStream): void {
        $s = count($matrix);
        $idx=0;
        $up=true;
        for($x=$s-1;$x>0;$x-=2){
            if($x===6)$x--;
            for($n=0;$n<$s;$n++){
                $y=($up?($s-1-$n):$n);
                for($xx=$x;$xx>=$x-1;$xx--){
                    if($matrix[$y][$xx]===null){
                        $bit=($idx<strlen($bitStream))?$bitStream[$idx]:'0';
                        $matrix[$y][$xx]=($bit==='1');
                        $idx++;
                    }
                }
            }
            $up=!$up;
        }
    }

    function applyMask0(array &$matrix): void {
        $s=count($matrix);
        for($y=0;$y<$s;$y++){
            for($x=0;$x<$s;$x++){
                if(isInFinder($x,$y,$s)||$x===6||$y===6)continue;
                if((($x+$y)%2)===0){
                    $matrix[$y][$x]=!$matrix[$y][$x];
                }
            }
        }
    }

    function calcFormatBits(string $ecc, int $mask): int {
        switch($ecc){
            case 'L': $e=0b01;break;
            case 'M': $e=0b00;break;
            case 'Q': $e=0b11;break;
            case 'H': $e=0b10;break;
            default:  $e=0b01;
        }
        if($mask<0||$mask>7)$mask=0;
        $f=($e<<3)|$mask;
        $poly=0x537;
        $v=$f<<10;
        for($i=4;$i>=0;$i--){
            if((($v>>($i+10))&1)===1){
                $v^=($poly<<$i);
            }
        }
        return (($f<<10)|($v&0x3ff))^0x5412;
    }

    function placeFormatBits(array &$matrix,string $ecc,int $mask): void {
        $v=calcFormatBits($ecc,$mask);
        $s=count($matrix);
        $pa=[[8,0],[8,1],[8,2],[8,3],[8,4],[8,5],[8,7],[8,8],[7,8],[5,8],[4,8],[3,8],[2,8],[1,8],[0,8]];
        $pb=[
          [$s-1-0,8],[$s-1-1,8],[$s-1-2,8],[$s-1-3,8],[$s-1-4,8],
          [$s-1-5,8],[$s-1-7,8],[$s-1-8,8],
          [8,$s-1-7],[8,$s-1-8],[8,$s-1-5],[8,$s-1-4],
          [8,$s-1-3],[8,$s-1-2],[8,$s-1-1]
        ];
        for($i=0;$i<15;$i++){
            $bit=(($v>>$i)&1)===1;
            [$xA,$yA]=$pa[$i]; $matrix[$yA][$xA]=$bit;
            [$xB,$yB]=$pb[$i]; $matrix[$yB][$xB]=$bit;
        }
    }

    // 1) Build bit stream for version 1-L
    $len=strlen($data);
    if($len>17){
        return "ERROR: Data too long for v1-L.";
    }
    $bitStream='0100'.str_pad(decbin($len),8,'0',STR_PAD_LEFT);
    for($i=0;$i<$len;$i++){
        $bitStream.=str_pad(decbin(ord($data[$i])),8,'0',STR_PAD_LEFT);
    }

    // Terminator
    $maxBits=19*8; // 152
    if(strlen($bitStream)<$maxBits){
        $r=$maxBits-strlen($bitStream);
        $t=min($r,4);
        $bitStream.=str_repeat('0',$t);
    }
    while(strlen($bitStream)%8!==0){
        $bitStream.='0';
    }
    // Pad codewords
    $blocks=str_split($bitStream,8);
    $pw=['11101100','00010001'];
    $pi=0;
    while(count($blocks)<19){
        $blocks[]=$pw[$pi%2];
        $pi++;
    }
    $dataBlock=array_map('bindec',$blocks);

    // 2) ECC (7 bytes)
    $ecc=computeRS($dataBlock,7);
    $all=array_merge($dataBlock,$ecc);

    // 3) Matrix
    $s=21;
    $m=array_fill(0,$s,array_fill(0,$s,null));
    placeFinderPatterns($m);
    placeTimingPatterns($m);

    // Convert codewords -> bits
    $joined='';
    foreach($all as $b){
        $joined.=str_pad(decbin($b),8,'0',STR_PAD_LEFT);
    }
    placeDataBits($m,$joined);
    applyMask0($m);
    placeFormatBits($m,'L',0);

    // 4) ASCII: '█'=black, '░'=white, + 1 module quiet zone
    $qr='';
    $quietRow=str_repeat('□',$s+2)."\n";
    $qr.=$quietRow;
    for($y=0;$y<$s;$y++){
        $row='□';
        for($x=0;$x<$s;$x++){
            $row.=($m[$y][$x]?'■':'□');
        }
        $row.='□'."\n";
        $qr.=$row;
    }
    $qr.=$quietRow;

    // Replace all newlines with '|'
    // First, remove the trailing newline (if any):
    $qr = rtrim($qr,"\n");
    // Then replace all remaining "\n" with "|"
    $qr = str_replace("\n", "|", $qr);

    return $qr;
}
?>
