<?php

class gFetch
{
    static public function get(string $url = '', $data = array())
    {
        $curl = curl_init();
        $query = [];
        foreach ($data as $key => $value) {
            $query[] = $data . '=' . $value;
        }

        if (str_contains($url, '?')) {
            $url .= '&' . implode('&', $query);
        } else {
            $url .= '?' . implode('&', $query);
        }
    }
    static public function post(string $url = '', array $data = array(), array $headers = array()) : array
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data, JSON_PRETTY_PRINT),
            CURLOPT_HTTPHEADER => $headers,
        ));

        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return [
            'status' => $status,
            'response' => json_decode($response, true)
        ];
    }
}