# Tenant Billing API Documentation

## Overview

The Tenant Billing API provides endpoints for managing payment proofs, viewing subscription status, and handling billing operations for authenticated tenants. All endpoints require tenant authentication and follow strict isolation rules.

## Base URL

```
https://your-domain.com/api/v1/billing
```

## Authentication

All billing API endpoints require:

1. **Sanctum Token**: Bearer token in `Authorization` header
2. **Tenant ID**: `X-Tenant-ID` header with valid tenant identifier
3. **User Access**: Authenticated user must belong to the specified tenant

### Required Headers

```http
Authorization: Bearer {sanctum_token}
X-Tenant-ID: {tenant_id}
Content-Type: application/json
Accept: application/json
```

## Response Format

### Success Response

```json
{
    "success": true,
    "data": {
        // Response data
    },
    "message": "Operation successful",
    "meta": {
        "timestamp": "2025-01-30T12:00:00Z",
        "version": "2.0.0"
    }
}
```

### Error Response

```json
{
    "success": false,
    "error": {
        "code": "ERROR_CODE",
        "message": "Error description",
        "details": {
            // Additional error details
        }
    },
    "meta": {
        "timestamp": "2025-01-30T12:00:00Z"
    }
}
```

## Endpoints

### 1. Get Billing Dashboard

**GET** `/api/v1/billing`

Returns current subscription status, recent payment proofs, and billing summary for the authenticated tenant.

#### Response

```json
{
    "success": true,
    "data": {
        "subscription": {
            "id": 1,
            "status": "active",
            "plan_name": "Premium Plan",
            "price": "2990.00",
            "currency": "ARS",
            "starts_at": "2025-01-01T00:00:00Z",
            "ends_at": "2025-02-01T00:00:00Z",
            "is_trial": false,
            "is_active": true,
            "days_remaining": 15
        },
        "payment_settings": {
            "max_file_size_mb": 5,
            "allowed_file_types": ["pdf", "jpg", "jpeg", "png"],
            "bank_account_info": "Bank: Example Bank\nAccount: 123456789\nCBU: 0000003100001234567890",
            "payment_instructions": "Please include your tenant ID in the payment reference"
        },
        "recent_payments": [
            {
                "id": 1,
                "amount": "2990.00",
                "currency": "ARS",
                "payment_method": "bank_transfer",
                "payment_date": "2025-01-15",
                "status": "approved",
                "status_display": "success",
                "payment_method_display": "Transferencia Bancaria",
                "file_count": 1,
                "created_at": "2025-01-15T14:30:00Z",
                "invoice_number": "INV-2025-001"
            }
        ],
        "billing_stats": {
            "total_payments": 5,
            "pending_payments": 0,
            "approved_payments": 4,
            "rejected_payments": 1,
            "total_amount_paid": "11960.00",
            "last_payment_date": "2025-01-15",
            "subscription_status": "active",
            "subscription_ends_at": "2025-02-01T00:00:00Z",
            "days_until_expiry": 15
        }
    },
    "message": "Billing dashboard retrieved successfully"
}
```

### 2. Submit Payment Proof

**POST** `/api/v1/billing`

Upload payment proof files and submit payment data for approval.

#### Request Body (multipart/form-data)

```http
POST /api/v1/billing
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
Content-Type: multipart/form-data

files: [file1.jpg, file2.pdf]
payment_method: bank_transfer
amount: 2990.00
payment_date: 2025-01-15
reference_number: TRANSF-001
payer_name: Juan Pérez
notes: Monthly subscription payment
```

#### Field Validation

- **files**: Required. Array of 1-5 files (PDF, JPG, JPEG, PNG). Max 5MB per file, 20MB total.
- **payment_method**: Required. One of: `bank_transfer`, `cash`, `mobile_money`, `other`
- **amount**: Required. Positive number with 2 decimal places
- **payment_date**: Required. Date string (YYYY-MM-DD). Cannot be in future, max 90 days old
- **reference_number**: Optional. Max 100 characters, alphanumeric with common separators
- **payer_name**: Optional. Max 255 characters, names with accents allowed
- **notes**: Optional. Max 1000 characters

#### Business Rules

- Payment amount must match expected subscription price (±1% tolerance)
- Duplicate submissions are prevented based on amount, date, and method
- Only pending payment proofs can be deleted/edited
- Files are stored in tenant-specific directories with secure access

#### Response

```json
{
    "success": true,
    "data": {
        "payment_proof": {
            "id": 123,
            "amount": "2990.00",
            "currency": "ARS",
            "payment_method": "bank_transfer",
            "payment_date": "2025-01-15",
            "reference_number": "TRANSF-001",
            "payer_name": "Juan Pérez",
            "status": "pending",
            "status_display": "warning",
            "payment_method_display": "Transferencia Bancaria",
            "notes": "Monthly subscription payment",
            "files": [
                {
                    "path": "payment-proofs/tenant_123/recibo_20250115_abc123.jpg",
                    "url": "https://your-domain.com/storage/payment-proofs/tenant_123/recibo_20250115_abc123.jpg",
                    "filename": "recibo_20250115_abc123.jpg",
                    "size": 1024576,
                    "last_modified": 1642255200
                }
            ],
            "total_file_size_mb": 1.02,
            "created_at": "2025-01-15T14:30:00Z"
        },
        "subscription": {
            "id": 1,
            "status": "active",
            "ends_at": "2025-02-01T00:00:00Z"
        }
    },
    "message": "Payment proof submitted successfully"
}
```

### 3. Get Payment History

**GET** `/api/v1/billing/history`

Get paginated list of all payment proofs for the tenant.

#### Query Parameters

- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 20, max: 100)
- `status` (optional): Filter by status (`pending`, `under_review`, `approved`, `rejected`)
- `payment_method` (optional): Filter by payment method
- `date_from` (optional): Filter payments from date (YYYY-MM-DD)
- `date_to` (optional): Filter payments to date (YYYY-MM-DD)

#### Example Request

```http
GET /api/v1/billing/history?page=1&per_page=10&status=pending
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

#### Response

```json
{
    "success": true,
    "data": [
        {
            "id": 123,
            "amount": "2990.00",
            "currency": "ARS",
            "payment_method": "bank_transfer",
            "payment_date": "2025-01-15",
            "reference_number": "TRANSF-001",
            "status": "pending",
            "status_display": "warning",
            "payment_method_display": "Transferencia Bancaria",
            "notes": "Monthly subscription payment",
            "file_count": 1,
            "total_file_size_mb": 1.02,
            "created_at": "2025-01-15T14:30:00Z",
            "invoice_number": "INV-2025-001",
            "subscription_id": 1
        }
    ],
    "meta": {
        "current_page": 1,
        "from": 1,
        "to": 10,
        "per_page": 10,
        "last_page": 3,
        "total": 25,
        "path": "https://your-domain.com/api/v1/billing/history",
        "links": {
            "first": "https://your-domain.com/api/v1/billing/history?page=1",
            "last": "https://your-domain.com/api/v1/billing/history?page=3",
            "prev": null,
            "next": "https://your-domain.com/api/v1/billing/history?page=2"
        },
        "timestamp": "2025-01-30T12:00:00Z",
        "version": "2.0.0"
    },
    "message": "Payment history retrieved successfully"
}
```

### 4. Get Payment Proof Details

**GET** `/api/v1/billing/payment-proofs/{id}`

Get detailed information about a specific payment proof.

#### Example Request

```http
GET /api/v1/billing/payment-proofs/123
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

#### Response

```json
{
    "success": true,
    "data": {
        "payment_proof": {
            "id": 123,
            "amount": "2990.00",
            "currency": "ARS",
            "payment_method": "bank_transfer",
            "payment_date": "2025-01-15",
            "reference_number": "TRANSF-001",
            "payer_name": "Juan Pérez",
            "status": "approved",
            "status_display": "success",
            "payment_method_display": "Transferencia Bancaria",
            "notes": "Monthly subscription payment",
            "rejection_reason": null,
            "review_notes": "Payment verified and approved",
            "reviewed_by": "Admin User",
            "reviewed_at": "2025-01-16T10:00:00Z",
            "files": [
                {
                    "path": "payment-proofs/tenant_123/recibo_20250115_abc123.jpg",
                    "url": "https://your-domain.com/storage/payment-proofs/tenant_123/recibo_20250115_abc123.jpg",
                    "filename": "recibo_20250115_abc123.jpg",
                    "size": 1024576,
                    "last_modified": 1642255200
                }
            ],
            "total_file_size_mb": 1.02,
            "created_at": "2025-01-15T14:30:00Z",
            "updated_at": "2025-01-16T10:00:00Z"
        },
        "subscription": {
            "id": 1,
            "status": "active",
            "plan_name": "Premium Plan"
        },
        "invoice": {
            "id": 45,
            "invoice_number": "INV-2025-001",
            "amount": "2990.00",
            "status": "paid"
        }
    },
    "message": "Payment proof details retrieved successfully"
}
```

### 5. Delete Payment Proof

**DELETE** `/api/v1/billing/payment-proofs/{id}`

Delete a pending payment proof. Only pending payment proofs can be deleted.

#### Example Request

```http
DELETE /api/v1/billing/payment-proofs/123
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

#### Response

```http
204 No Content
```

### 6. Download Payment Proof File

**GET** `/api/v1/billing/payment-proofs/{id}/files/{file_path}`

Download a specific file from a payment proof. The file path must be URL encoded.

#### Example Request

```http
GET /api/v1/billing/payment-proofs/123/files/payment-proofs%2Ftenant_123%2Frecibo_20250115_abc123.jpg
Authorization: Bearer {token}
X-Tenant-ID: {tenant_id}
```

#### Response

```http
200 OK
Content-Type: image/jpeg
Content-Disposition: attachment; filename="recibo_20250115_abc123.jpg"
Cache-Control: no-cache, no-store, must-revalidate
Pragma: no-cache
Expires: 0

[file binary data]
```

## Error Codes

### Authentication Errors

- `UNAUTHORIZED`: Tenant authentication required
- `TENANT_ID_MISSING`: X-Tenant-ID header is required
- `TENANT_NOT_FOUND`: Tenant not found
- `TENANT_INACTIVE`: Tenant is inactive
- `FORBIDDEN`: Access denied (user doesn't belong to tenant)

### Validation Errors

- `VALIDATION_ERROR`: Request validation failed
- `BILLING_DASHBOARD_ERROR`: Error retrieving billing information
- `PAYMENT_PROOF_SUBMISSION_ERROR`: Error submitting payment proof
- `PAYMENT_PROOF_RETRIEVAL_ERROR`: Error retrieving payment proof
- `PAYMENT_PROOF_DELETION_ERROR`: Error deleting payment proof
- `FILE_DOWNLOAD_ERROR`: Error downloading file

### Business Logic Errors

- `NOT_FOUND`: Payment proof not found
- `SUBSCRIPTION_REQUIRED`: No active subscription found
- `DUPLICATE_PAYMENT`: Duplicate payment submission
- `AMOUNT_MISMATCH`: Payment amount doesn't match expected amount
- `FILE_NOT_ALLOWED`: File type not allowed
- `FILE_TOO_LARGE`: File exceeds size limit

## Security Features

### Tenant Isolation

- All database queries are scoped to tenant ID
- File storage is organized by tenant ID
- Cross-tenant access is prevented at multiple levels

### File Security

- Files are stored in secure tenant-specific directories
- File access is validated against payment proof records
- File uploads are validated for type, size, and content
- Malicious file detection is implemented

### Rate Limiting

- API requests are rate-limited per tenant
- File upload attempts are tracked and limited
- Suspicious activity is logged and monitored

### Audit Logging

- All payment proof operations are logged
- File access is tracked with IP and user information
- Failed authentication attempts are monitored

## Usage Examples

### JavaScript/Example

```javascript
// Get billing dashboard
const getBillingDashboard = async () => {
    const response = await fetch('/api/v1/billing', {
        method: 'GET',
        headers: {
            'Authorization': `Bearer ${token}`,
            'X-Tenant-ID': tenantId,
            'Content-Type': 'application/json',
        },
    });

    const data = await response.json();
    if (data.success) {
        console.log('Subscription:', data.data.subscription);
        console.log('Recent payments:', data.data.recent_payments);
    }
};

// Submit payment proof
const submitPaymentProof = async (files, paymentData) => {
    const formData = new FormData();
    files.forEach(file => formData.append('files[]', file));
    formData.append('payment_method', paymentData.paymentMethod);
    formData.append('amount', paymentData.amount);
    formData.append('payment_date', paymentData.paymentDate);
    formData.append('reference_number', paymentData.referenceNumber);
    formData.append('payer_name', paymentData.payerName);
    formData.append('notes', paymentData.notes);

    const response = await fetch('/api/v1/billing', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${token}`,
            'X-Tenant-ID': tenantId,
        },
        body: formData,
    });

    const data = await response.json();
    if (data.success) {
        console.log('Payment proof submitted:', data.data.payment_proof);
    }
};

// Get payment history
const getPaymentHistory = async (page = 1) => {
    const response = await fetch(`/api/v1/billing/history?page=${page}`, {
        method: 'GET',
        headers: {
            'Authorization': `Bearer ${token}`,
            'X-Tenant-ID': tenantId,
        },
    });

    const data = await response.json();
    if (data.success) {
        console.log('Payment history:', data.data);
        console.log('Pagination:', data.meta);
    }
};
```

### cURL Examples

```bash
# Get billing dashboard
curl -X GET "https://your-domain.com/api/v1/billing" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-Tenant-ID: YOUR_TENANT_ID" \
  -H "Content-Type: application/json"

# Submit payment proof
curl -X POST "https://your-domain.com/api/v1/billing" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-Tenant-ID: YOUR_TENANT_ID" \
  -F "files[]=@/path/to/receipt.jpg" \
  -F "files[]=@/path/to/invoice.pdf" \
  -F "payment_method=bank_transfer" \
  -F "amount=2990.00" \
  -F "payment_date=2025-01-15" \
  -F "reference_number=TRANSF-001" \
  -F "payer_name=Juan Pérez" \
  -F "notes=Monthly payment"

# Get payment history
curl -X GET "https://your-domain.com/api/v1/billing/history?page=1&per_page=10" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-Tenant-ID: YOUR_TENANT_ID" \
  -H "Content-Type: application/json"

# Delete payment proof
curl -X DELETE "https://your-domain.com/api/v1/billing/payment-proofs/123" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-Tenant-ID: YOUR_TENANT_ID" \
  -H "Content-Type: application/json"
```

## Testing

The API includes comprehensive validation and error handling. Test endpoints with:

1. **Valid scenarios**: Normal workflow with proper authentication
2. **Invalid authentication**: Missing/invalid tokens or tenant IDs
3. **Invalid data**: Malformed requests, missing fields, invalid values
4. **Security testing**: Attempted cross-tenant access, file injection attempts
5. **Edge cases**: Large files, special characters, concurrent uploads

## Support

For technical support or API issues:

1. Check the application logs for detailed error information
2. Verify tenant authentication and permissions
3. Ensure file formats and sizes meet requirements
4. Contact system administrator with specific error codes and details