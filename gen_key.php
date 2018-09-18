<?php
  // Generate a private key based on a 2048 bit RSA algorithm
  $privateKey = openssl_pkey_new(array(
      'private_key_bits' => 2048,      // Size of Key.
      'private_key_type' => OPENSSL_KEYTYPE_RSA,
  ));

  // Save the private key to 'private.key' file. This file should not reside on the server but locally
  openssl_pkey_export_to_file($privateKey, 'private.key');

  // Generate a public key for the private key
  $a_key = openssl_pkey_get_details($privateKey);
  // Save the generated public key in 'public.key' file. This file is public and used for encryption
  file_put_contents('public.key', $a_key['key']);

  // Free the private Key.
  openssl_free_key($privateKey);

  // Free the public Key.
  openssl_free_key($publicKey);
?>
