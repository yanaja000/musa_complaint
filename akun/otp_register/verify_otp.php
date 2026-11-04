<?php

/**
 * OTP REGISTRASI - proses verifikasi OTP (JSON). POST: csrf_token, email, otp
 */

require_once "../../config/database.php";
require_once "../../config/otp_endpoint.php";

otpEndpointVerifikasi($conn, OTP_PURPOSE_REGISTER);
