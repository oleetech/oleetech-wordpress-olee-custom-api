## Register API Endpoint

### URL
`/wp-json/custom/v1/register`

### Method
`POST`

### Description
Registers a new user with the provided details including username, email, password, and contact number.

### Headers
- `Content-Type: application/json`
- `Authorization: Bearer YOUR_JWT_TOKEN`

### Request Parameters

| Parameter       | Type     | Description                          | Required |
|-----------------|----------|--------------------------------------|----------|
| `username`      | string   | Username for the new account         | Yes      |
| `email`         | string   | Email address of the user            | Yes      |
| `password`      | string   | Password for the new account         | Yes      |
| `contact_number`| string   | Contact number of the user           | Yes      |

### Example Request
```bash
curl -X POST https://kreatech.ca/wp-json/custom/v1/register \
-H "Content-Type: application/json" \
-H "Authorization: Bearer YOUR_JWT_TOKEN" \
-d '{
    "username": "john_doe",
    "email": "john.doe@example.com",
    "password": "securepassword123",
    "contact_number": "12345678901"
}'
