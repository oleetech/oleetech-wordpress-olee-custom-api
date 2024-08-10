# API Documentation

## 1. Get All Appointments

**URL:** `/wp-json/custom/v1/appointment/all`  
**Method:** `GET`  
**Description:** Retrieve a list of all appointments.

**Headers:**
- **Authorization:** Bearer `YOUR_JWT_TOKEN`

**Success Response:**
**Status Code:** 200 OK  
**Body:**
```json
[
    {
        "id": 1,
        "date": "2024-08-01",
        "time": "10:00",
        "name": "John Doe",
        "email": "john.doe@example.com",
        "phone": "+1234567890",
        "appointment_status": "requested"
    },
    ...
]
```

# Get Appointment by ID

## Endpoint
`GET /wp-json/custom/v1/appointment/(?P<id>\d+)`

## Description
Retrieve detailed information about a specific appointment based on its ID.

## Parameters
- **id** (path parameter): The ID of the appointment to retrieve. Must be an integer.

## Request Example
```http
GET /wp-json/custom/v1/appointment/123
```

# Create Appointment

## Endpoint
`POST /wp-json/custom/v1/appointment/create`

## Description
Create a new appointment. This endpoint requires authentication and only logged-in users can create an appointment.

## Request

### Headers
- **Authorization**: Bearer token (JWT Authentication required)
- **Content-Type**: application/json

### Request Body
**Content-Type**: application/json

**Example Request Body**:
```json
{
    "appointment_date": "2024-08-10",
    "appointment_time": "14:00",
    "appointment_name": "John Doe",
    "appointment_email": "john.doe@example.com",
    "appointment_phone": "+1234567890",
    "appointment_status": "requested"  // Optional field
}
```

# Update Appointment

## Endpoint
`PUT /wp-json/custom/v1/appointment/update/{id}`

## Description
Update an existing appointment by its ID. This endpoint requires authentication, and only authorized users can update an appointment.

## Request

### Headers
- **Authorization**: Bearer token (JWT Authentication required)
- **Content-Type**: application/json

### URL Parameters
- **id**: The ID of the appointment to be updated. (required)

### Request Body
**Content-Type**: application/json

**Example Request Body**:
```json
{
    "appointment_date": "2024-08-15",
    "appointment_time": "16:00",
    "appointment_name": "Jane Doe",
    "appointment_email": "jane.doe@example.com",
    "appointment_phone": "+0987654321",
    "appointment_status": "confirmed"  // Optional field
}
```

# Delete Appointment

## Endpoint
`DELETE /wp-json/custom/v1/appointment/delete/{id}`

## Description
Delete an existing appointment by its ID. This endpoint requires authentication, and only authorized users can delete an appointment.

## Request

### Headers
- **Authorization**: Bearer token (JWT Authentication required)

### URL Parameters
- **id**: The ID of the appointment to be deleted. (required)

## Response

### Success (200 OK)
**Content-Type**: application/json

**Response Body**:
```json
{
    "message": "Appointment deleted successfully",
    "id": 123
}
```


# Update Appointment Status

## Endpoint
`POST /wp-json/custom/v1/appointment/update-status/{id}`

## Description
Update the status of an existing appointment by its ID. This endpoint requires authentication, and only authorized users can update the status of an appointment.

## Request

### Headers
- **Authorization**: Bearer token (JWT Authentication required)

### URL Parameters
- **id**: The ID of the appointment whose status is to be updated. (required)

### Body Parameters
- **appointment_status**: The new status for the appointment. (required)

**Body Example**:
```json
{
    "appointment_status": "approved"
}
```

# Get Appointments by User ID

## Endpoint
`GET /wp-json/custom/v1/appointment/user/{user_id}`

## Description
Retrieve all appointments associated with a specific user ID. This endpoint requires authentication and only authorized users can access their own appointments.

## Request

### Headers
- **Authorization**: Bearer token (JWT Authentication required)

### URL Parameters
- **user_id**: The ID of the user whose appointments are to be fetched. (required)

## Response

### Success (200 OK)
**Content-Type**: application/json

**Response Body**:
```json
[
    {
        "id": 123,
        "date": "2024-08-01",
        "time": "10:00",
        "name": "John Doe",
        "email": "john.doe@example.com",
        "phone": "123-456-7890",
        "appointment_status": "approved"
    },
    {
        "id": 124,
        "date": "2024-08-02",
        "time": "14:00",
        "name": "Jane Smith",
        "email": "jane.smith@example.com",
        "phone": "987-654-3210",
        "appointment_status": "pending"
    }
]
```

### 3. Create Appointment

- **URL:** `/wp-json/custom/v1/appointments`
- **Method:** `POST`
- **Auth Required:** Yes (User must be logged in)
- **Content-Type:** `application/json`

#### Request Body

- `date` (string, format: `YYYY-MM-DD`) - The date of the appointment.
- `time` (string, format: `HH:MM:SS`) - The time of the appointment.
- `name` (string) - The name of the person making the appointment.
- `email` (string) - The email address of the person making the appointment.
- `phone` (string) - The phone number of the person making the appointment.

#### Example Request

```http
POST /wp-json/custom/v1/appointments HTTP/1.1
Host: example.com
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json

{
  "date": "2024-08-15",
  "time": "14:30:00",
  "name": "John Doe",
  "email": "john.doe@example.com",
  "phone": "+1234567890"
}
```
### 4. Update Appointment

- **URL:** `/wp-json/custom/v1/appointments/(?P<id>\d+)`
- **Method:** `POST`
- **Auth Required:** Yes (User must be logged in)
- **Content-Type:** `application/json`

#### Request Parameters

- `id` (integer) - The ID of the appointment to be updated.

#### Request Body

- `date` (string, format: `YYYY-MM-DD`) - The new date of the appointment.
- `time` (string, format: `HH:MM:SS`) - The new time of the appointment.
- `name` (string) - The new name of the person making the appointment.
- `email` (string) - The new email address of the person making the appointment.
- `phone` (string) - The new phone number of the person making the appointment.

#### Example Request

```http
POST /wp-json/custom/v1/appointments/123 HTTP/1.1
Host: example.com
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json

{
  "date": "2024-08-20",
  "time": "15:00:00",
  "name": "Jane Doe",
  "email": "jane.doe@example.com",
  "phone": "+0987654321"
}
```
### 5. Delete Appointment

- **URL:** `/wp-json/custom/v1/appointments/(?P<id>\d+)`
- **Method:** `DELETE`
- **Auth Required:** Yes (User must be logged in)
- **Content-Type:** `application/json`

#### Request Parameters

- `id` (integer) - The ID of the appointment to be deleted.

#### Example Request

```http
DELETE /wp-json/custom/v1/appointments/123 HTTP/1.1
Host: example.com
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json
```



