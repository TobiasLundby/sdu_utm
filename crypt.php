<?php
  // Functions are based on https://www.virendrachandak.com/techtalk/encryption-using-php-openssl/ but modified to fit use case.
  
  function encrypt($plaintext){
    $plaintext = gzcompress($plaintext);

    // Get the public Key of the recipient
    $publicKey = openssl_pkey_get_public('file://public.key');
    $a_key = openssl_pkey_get_details($publicKey);

    // Encrypt the data in small chunks and then combine and send it.
    $chunkSize = ceil($a_key['bits'] / 8) - 11;
    $output = '';

    while ($plaintext)
    {
        $chunk = substr($plaintext, 0, $chunkSize);
        $plaintext = substr($plaintext, $chunkSize);
        $encrypted = '';
        if (!openssl_public_encrypt($chunk, $encrypted, $publicKey))
        {
            die('Failed to encrypt data');
        }
        $output .= $encrypted;
    }
    openssl_free_key($publicKey);

    // This is the final encrypted data to be sent to the recipient
    $encrypted = $output;

    return base64_encode($encrypted);
  }

  function decrypt($encrypted){
    $encrypted = base64_decode($encrypted);

    // Get the private Key
    if (!$privateKey = openssl_pkey_get_private('file://private.key'))
    {
        die('Private Key failed');
    }
    $a_key = openssl_pkey_get_details($privateKey);

    // Decrypt the data in the small chunks
    $chunkSize = ceil($a_key['bits'] / 8);
    $output = '';

    while ($encrypted)
    {
        $chunk = substr($encrypted, 0, $chunkSize);
        $encrypted = substr($encrypted, $chunkSize);
        $decrypted = '';
        if (!openssl_private_decrypt($chunk, $decrypted, $privateKey))
        {
            die('Failed to decrypt data');
        }
        $output .= $decrypted;
    }
    openssl_free_key($privateKey);

    // Uncompress the unencrypted data.
    return gzuncompress($output);
  }

?>
