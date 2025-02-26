<?php
ini_set('memory_limit', -1);
function err($s) {
    die($s . "\n");
}

$file = @$_SERVER['argv'][1];
$extract = @$_SERVER['argv'][2];
if (!$extract) {
    $extract = 'resb';
}

if (empty($file) || !file_exists($file)) {
    err("file not found: " . $file);
}
function handleItem($item, $extract) {
    preg_match_all('#<response base64="true"><!\[CDATA\[([^\]]+)\]\]>#', $item, $matches);
    preg_match_all('#<request base64="true"><!\[CDATA\[([^\]]+)\]\]>#', $item, $reqmatches);
    preg_match_all('#<url><!\[CDATA\[(.*?)\]\]>#', $item, $urlmatches);
    if (!$matches[1]) { return; }
    foreach ($matches[1] as $key => $res) {
        $req = base64_decode($reqmatches[1][$key]);
        $req = explode("\r\n\r\n", $req);
        $reqheaders = array_shift($req);
        $reqheaders = explode("\r\n", $reqheaders);
        $reqline = array_shift($reqheaders);
        $reqiheaders = [];
        foreach($reqheaders as $headerval) {
            $headerval = explode(': ', $headerval);
            $reqhkey = strtolower(trim(array_shift($headerval)));
            $reqiheaders[$reqhkey] = implode(': ', $headerval);
        }
        $reqpathpart = explode(' ', $reqline);
        $reqmethod = array_shift($reqpathpart);
        array_pop($reqpathpart);
        $reqpath = implode(' ', $reqpathpart);
        $reqheaders = implode("\r\n", $reqheaders);
        $req = implode("\r\n\r\n", $req);
        $res = base64_decode($res);
        $res = explode("\r\n\r\n", $res);
        $resheaders = array_shift($res);
        $resheaders = explode("\r\n", $resheaders);
        $rescode = array_shift($resheaders);
        $resiheaders = [];
        foreach($resheaders as $headerval) {
            $headerval = explode(': ', $headerval);
            $reshkey = strtolower(trim(array_shift($headerval)));
            $resiheaders[$reshkey] = implode(': ', $headerval);
        }
        $resheaders = implode("\r\n", $resheaders);
        $res = implode("\r\n\r\n", $res);
        $rawoutput = preg_match('#re[qs][a-z]#', $extract);
    
        if (strpos($extract, 'reqp') !== false) {
            echo "\n";
            echo $reqpath;
        }
        if (strpos($extract, 'reqm') !== false) {
            echo "\n";
            echo $reqmethod;
        }
        if (strpos($extract, 'reql') !== false) {
            echo "\n";
            echo $reqline;
        }
        if (strpos($extract, 'reqh') !== false) {
            echo "\n";
            echo $reqheaders;
        }
        if (strpos($extract, 'reqb') !== false) {
            echo "\n";
            echo $req;
        }
        if (strpos($extract, 'resc') !== false) {
            echo "\n";
            echo $rescode;
        }
        if (strpos($extract, 'resh') !== false) {
            echo "\n";
            echo $resheaders;
        }
        if (strpos($extract, 'resi') !== false) {
            echo "\n";
            echo $resiheaders;
        }
        if (strpos($extract, 'resb') !== false) {
            echo "\n";
            echo $res;
        }
        $json = [];
        if (strpos($extract, 'jsonh') !== false || strpos($extract, 'all') !== false) {
            $json['reqm'] = $reqmethod;
            $json['reqp'] = $reqpath;
            $json['reql'] = $reqline;
            $json['reqh'] = $reqheaders;
            $json['reqi'] = $reqiheaders;
            $json['resc'] = $rescode;
            $json['resh'] = $resheaders;
            $json['resi'] = $resiheaders;
        }
        if (strpos($extract, 'jsonb') !== false || strpos($extract, 'all') !== false) {
            $json['reqb'] = $req;
            $json['resb'] = $res;
        }
        if (strpos($extract, 'jsonreq') !== false) {
            $json['reqm'] = $reqmethod;
            $json['reqp'] = $reqpath;
            $json['reql'] = $reqline;
            $json['reqh'] = $reqheaders;
            $json['reqi'] = $reqiheaders;
            $json['reqb'] = $req;
        }
        if (strpos($extract, 'jsonres') !== false) {
            $json['resc'] = $rescode;
            $json['resh'] = $resheaders;
            $json['resi'] = $resiheaders;
            $json['resb'] = $res;
        }
        if ($json) {
            if ($rawoutput) {
                echo "\n";
            }
            echo json_encode($json, JSON_UNESCAPED_SLASHES);
        }
        print("\n");
        print("\n");
    }
}

$handle = fopen($file, "r");
if ($handle) {
    $currentitem = '';
    while (($line = fgets($handle)) !== false) {
        if (strpos($line, '  <item>') === 0) {
            handleItem($currentitem, $extract);
            $currentitem = '';
            continue;
        }
        $currentitem .= $line;
    }
    handleItem($currentitem, $extract);
    fclose($handle);
}
