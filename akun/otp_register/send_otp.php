<?php

/**
 * OTP REGISTRASI - proses pengiriman OTP (JSON). POST: csrf_token, role, email
 */

require_once "../../config/database.php";
require_once "../../config/otp_endpoint.php";

otpEndpointKirim($conn, OTP_PURPOSE_REGISTER);
