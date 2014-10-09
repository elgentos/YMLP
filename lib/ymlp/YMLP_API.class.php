<?php

class YMLP_API {
    var $FoutMelding;
    var $ApiUrl = "www.ymlp.com/api/";
    var $APIGebruikersnaam;
    var $APISleutel;
    var $Secure = false;
    var $Curl = true;
    var $CurlAvailable = true;

    function YMLP_API($APISleutel=null,$APIGebruikersnaam=null,$secure=false) {
        $this->APISleutel = $APISleutel;
        $this->APIGebruikersnaam = $APIGebruikersnaam;
        $this->Secure = $secure;
        $this->CurlAvailable = function_exists( 'curl_init' ) && function_exists( 'curl_setopt' );
    }

    function useSecure($val) {
        if ($val===true){
            $this->Secure = true;
        } else {
            $this->Secure = false;
        }
    }

    function doCall($method = '',$params = array()) {

        $params["Sleutel"] = $this->APISleutel;
        $params["Gebruikersnaam"] = $this->APIGebruikersnaam;
        $params["output"] = "PHP";
        $this->FoutMelding = "";

        $postdata = null;

        foreach ( $params as $k => $v )
            $postdata .= '&' . $k . '=' .rawurlencode(utf8_encode($v));

        if ( $this->Curl && $this->CurlAvailable )  {
            $ch = curl_init();
            curl_setopt( $ch, CURLOPT_POST, 1 );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, $postdata );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            if ($this->Secure){
                curl_setopt( $ch, CURLOPT_URL, "https://" .$this->ApiUrl . $method );
            } else {
                curl_setopt( $ch, CURLOPT_URL, "http://" .$this->ApiUrl . $method );
            }

            $response = curl_exec( $ch );
            if(curl_errno($ch)) {
                $this->FoutMelding = curl_error($ch);
                return false;
                }
            }
        else {
            $this->ApiUrl = parse_url( "http://" .$this->ApiUrl . $method);
            $payload = "POST " . $this->ApiUrl["path"] . "?" . $this->ApiUrl["query"] . " HTTP/1.0\r\n";
            $payload .= "Host: " . $this->ApiUrl["host"] . "\r\n";
            $payload .= "User-Agent: YMLP_API\r\n";
            $payload .= "Content-type: application/x-www-form-urlencoded\r\n";
            $payload .= "Content-length: " . strlen($postdata) . "\r\n";
            $payload .= "Connection: close \r\n\r\n";
            $payload .= $postdata;

            ob_start();
            if ($this->Secure){
                $sock = fsockopen("ssl://".$this->ApiUrl["host"], 443, $errno, $errstr);
            } else {
                $sock = fsockopen($this->ApiUrl["host"], 80, $errno, $errstr);
            }

            if(!$sock) {
                $this->FoutMelding = "FOUT $errno: $errstr";
                ob_end_clean();
                return false;
            }

            $response = "";
            fwrite($sock, $payload);
            while(!feof($sock)) {
                $response .= fread($sock,8192);
            }
            fclose($sock);
            ob_end_clean();

            list($throw, $response) = explode("\r\n\r\n", $response, 2);
        }

        if(ini_get("magic_quotes_runtime")) $response = stripslashes($response);

        if (strtoupper($params["output"]) == "PHP" ) {
            $serial = unserialize($response);
            if ($response && $serial === false) {
                $this->FoutMelding = "Onleesbaar Antwoord: " . $response;
                return false;
                }
            else {
                $response = $serial;
                }
            }

    return $response;
    }


    function Ping() {
        return $this->doCall("Ping");
    }

    function GroepenLijst() {
        return $this->doCall("Groepen.Lijst");
    }

    function GroepenToevoegen($GroepNaam = '') {
        $params = array();
        $params["GroepNaam"] = $GroepNaam;
        return $this->doCall("Groepen.Toevoegen", $params);
    }

    function GroepenVerwijderen($GroepNummer = '') {
        $params = array();
        $params["GroepNummer"] = $GroepNummer;
        return $this->doCall("Groepen.Verwijderen", $params);
    }

    function GroepenBijwerken($GroepNummer = '', $GroepNaam = '') {
        $params = array();
        $params["GroepNummer"] = $GroepNummer;
        $params["GroepNaam"] = $GroepNaam;
        return $this->doCall("Groepen.Bijwerken", $params);
    }

    function GroepenLeegmaken($GroepNummer = '') {
        $params = array();
        $params["GroepNummer"] = $GroepNummer;
        return $this->doCall("Groepen.Leegmaken", $params);
    }

    function VeldenLijst() {
        return $this->doCall("Velden.Lijst");
    }

    function VeldenToevoegen($VeldNaam = '', $Alias = '', $StandaardWaarde = '', $HoofdlettersCorrigeren = '') {
        $params = array();
        $params["VeldNaam"] = $VeldNaam;
        $params["Alias"] = $Alias;
        $params["StandaardWaarde"] = $StandaardWaarde;
        $params["HoofdlettersCorrigeren"] = $HoofdlettersCorrigeren;
        return $this->doCall("Velden.Toevoegen", $params);
    }

    function VeldenVerwijderen($VeldNummer = '') {
        $params = array();
        $params["VeldNummer"] = $VeldNummer;
        return $this->doCall("Velden.Verwijderen", $params);
    }

    function VeldenBijwerken($VeldNummer = '', $VeldNaam = '', $Alias = '', $StandaardWaarde = '', $HoofdlettersCorrigeren = '') {
        $params = array();
        $params["VeldNummer"] = $VeldNummer;
        $params["VeldNaam"] = $VeldNaam;
        $params["Alias"] = $Alias;
        $params["StandaardWaarde"] = $StandaardWaarde;
        $params["HoofdlettersCorrigeren"] = $HoofdlettersCorrigeren;
        return $this->doCall("Velden.Bijwerken", $params);
    }

    function ContactenToevoegen($Email = '', $OverigeVelden = '', $GroepNummer = '', $NegeerUitgeschrevenBounced = '') {
        $params = array();
        $params["Email"] = $Email;
        if (!is_array($OverigeVelden)) $OverigeVelden=array();
        foreach ($OverigeVelden as $key=>$value) {
            $params[$key] = $value;
            }
        $params["GroepNummer"] = $GroepNummer;
        $params["NegeerUitgeschrevenBounced"] = $NegeerUitgeschrevenBounced;
        return $this->doCall("Contacten.Toevoegen", $params);
    }

    function ContactenUitschrijven($Email = '') {
        $params = array();
        $params["Email"] = $Email;
        return $this->doCall("Contacten.Uitschrijven", $params);
    }

    function ContactenVerwijderen($Email = '', $GroepNummer = '') {
        $params = array();
        $params["Email"] = $Email;
        $params["GroepNummer"] = $GroepNummer;
        return $this->doCall("Contacten.Verwijderen", $params);
    }

    function ContactenDetail($Email = '') {
        $params = array();
        $params["Email"] = $Email;
        return $this->doCall("Contacten.Detail", $params);
    }

    function ContactenLijst($GroepNummer = '', $VeldNummer = '', $Pagina = '', $AantalPerPagina = '', $StartDatum = '', $StopDatum = '') {
        $params = array();
        $params["GroepNummer"] = $GroepNummer;
        $params["VeldNummer"] = $VeldNummer;
        $params["Pagina"] = $Pagina;
        $params["AantalPerPagina"] = $AantalPerPagina;
        $params["StartDatum"] = $StartDatum;
        $params["StopDatum"] = $StopDatum;
        return $this->doCall("Contacten.Lijst", $params);
    }

    function ContactenLijstUitschrijvers($VeldNummer = '', $Pagina = '', $AantalPerPagina = '', $StartDatum = '', $StopDatum = '') {
        $params = array();
        $params["VeldNummer"] = $VeldNummer;
        $params["Pagina"] = $Pagina;
        $params["AantalPerPagina"] = $AantalPerPagina;
        $params["StartDatum"] = $StartDatum;
        $params["StopDatum"] = $StopDatum;
        return $this->doCall("Contacten.LijstUitschrijvers", $params);
    }

    function ContactenLijstVerwijderd($VeldNummer = '', $Pagina = '', $AantalPerPagina = '', $StartDatum = '', $StopDatum = '') {
        $params = array();
        $params["VeldNummer"] = $VeldNummer;
        $params["Pagina"] = $Pagina;
        $params["AantalPerPagina"] = $AantalPerPagina;
        $params["StartDatum"] = $StartDatum;
        $params["StopDatum"] = $StopDatum;
        return $this->doCall("Contacten.LijstVerwijderd", $params);
    }

    function ContactenLijstBounced($VeldNummer = '', $Pagina = '', $AantalPerPagina = '', $StartDatum = '', $StopDatum = '') {
        $params = array();
        $params["VeldNummer"] = $VeldNummer;
        $params["Pagina"] = $Pagina;
        $params["AantalPerPagina"] = $AantalPerPagina;
        $params["StartDatum"] = $StartDatum;
        $params["StopDatum"] = $StopDatum;
        return $this->doCall("Contacten.LijstBounced", $params);
    }


}
