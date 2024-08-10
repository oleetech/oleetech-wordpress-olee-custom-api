## API Endpoints

### Create Answer
**Endpoint:** `POST /wp-json/custom/v1/answers`

**Description:** Create a new answer for a question.

**Request:**
```json
{
  "question_id": "integer",
  "content": "string"
}
```

## Create Multiple Answers

**Endpoint:** `POST /wp-json/custom/v1/multiple-answers`

**Description:**
This endpoint allows users to create multiple answers in one request. Each answer is associated with a question ID and contains the answer content. The user must be logged in to use this endpoint.

**Request Headers:**
- `Authorization`: Bearer token (JWT or Basic Authentication)

**Request Body:**
The request should be a JSON array containing multiple answers. Each answer object in the array must include the following fields:
- `id`: The question ID associated with the answer.
- `answer`: The content of the answer.

**Example Request Body:**
```json
[
    {
        "id": 1,
        "answer": "This is the answer to question 1."
    },
    {
        "id": 2,
        "answer": "This is the answer to question 2."
    }
]
```

## Endpoint for Retrieving All Answers
URL: /custom/v1/answers
Method: GET
Description: Retrieve all answers.
Permissions: Public.

### Response:

Success (200 OK):
```json
[
    {
        "id": "integer",
        "content": "string",
        "question_id": "integer"
    }
]
```
## Retrieving a Specific Answer by ID
URL: /custom/v1/answers/{id}
Method: GET
Description: Retrieve a specific answer by its ID.
Permissions: Public.

### Response:

Success (200 OK):
 ```json

 {
    "id": "integer",
    "answer": "string",
    "q_id": "integer"
}
```


## Updating an Answer
URL: /custom/v1/answers/{id}
Method: PUT
Description: Update an existing answer.
Permissions: Logged-in users only.

### Request Body:

```json
{
    "content": "string",
    "question_id": "integer"
}
```


## Deleting an Answer
URL: /custom/v1/answers/{id}
Method: DELETE
Description: Delete an existing answer.
Permissions: Logged-in users only.

### Response:

Success (200 OK):

```json
{
    "message": "Answer deleted successfully"
}

```

## Getting Answers by Question ID
URL: /custom/v1/question-answers/{id}
Method: GET
Description: Retrieve answers related to a specific question ID.
Permissions: Public.

### Response:

Success (200 OK):

```json
[
    {
        "answer": {
            "id": "integer",
            "title": "string",
            "content": "string",
            "author": "string",
            "date": "string",
            "featured_image": "string"
        },
        "question": {
            "id": "integer",
            "title": "string",
            "content": "string",
            "author": "string",
            "date": "string",
            "featured_image": "string"
        }
    }
]

```

## Getting Questions and Answers by User ID
URL: /custom/v1/user/questions-answers/{id}
Method: GET
Description: Retrieve questions and answers by user ID.
Permissions: Public.

### Response:

Success (200 OK):

``` json 
[
    {
        "answer": {
            "id": "integer",
            "title": "string",
            "content": "string",
            "author": "string",
            "date": "string",
            "featured_image": "string"
        },
        "question": {
            "id": "integer",
            "title": "string",
            "content": "string",
            "author": "string",
            "date": "string",
            "featured_image": "string"
        }
    }
]

```

## Getting Answers by Specific Date and User ID
URL: /custom/v1/answers-by-date/{date}
Method: GET
Description: Retrieve answers by a specific date and user ID.
Permissions: Logged-in users only.

### Response:

Success (200 OK):
```json
[
    {
        "id": "integer",
        "content": "string",
        "question_id": "integer"
    }
]

```

## Getting Answers by Specific Date for All Users
URL: /custom/v1/answers-by-date
Method: GET
Description: Retrieve answers by a specific date for all users.
Permissions: Logged-in users only.

### Response:

Success (200 OK):
```json
[
    {
        "id": "integer",
        "content": "string",
        "question_id": "integer"
    }
]

```

## Getting Answers by Date Range for All Users
URL: /custom/v1/answers-by-date-range
Method: GET
Description: Retrieve answers by a date range for all users.
Permissions: Logged-in users only.

### Response:

Success (200 OK):
```json
[
    {
        "id": "integer",
        "content": "string",
        "question_id": "integer"
    }
]

```

## Get Answers by Date Range and User ID

**Endpoint:** `GET /wp-json/custom/v1/answers`

**Description:**
This endpoint retrieves answers submitted by a specific user within a given date range. This is useful for fetching a list of answers for a user over a particular period.

**Query Parameters:**
- `user_id`: (required) The ID of the user whose answers you want to retrieve.
- `start_date`: (required) The start date of the range in the format `YYYY-MM-DD`.
- `end_date`: (required) The end date of the range in the format `YYYY-MM-DD`.

**Example Request:**
GET /wp-json/custom/v1/answers?user_id=1&start_date=2024-01-01&end_date=2024-01-31


**Request Headers:**
- `Authorization`: Bearer token (JWT or Basic Authentication)

**Responses:**

- **Success (200 OK):**

    **Response Body:**
    ```json
    [
        {
            "id": 101,
            "question_id": 5,
            "answer": "Answer content for question 5.",
            "user_id": 1,
            "created_at": "2024-01-10T14:32:00Z"
        },
        {
            "id": 102,
            "question_id": 6,
            "answer": "Answer content for question 6.",
            "user_id": 1,
            "created_at": "2024-01-15T09:21:00Z"
        }
    ]
    ```

- **Error (400 Bad Request):**

    **Response Body:**
    ```json
    {
        "message": "Invalid or missing query parameters. Ensure 'user_id', 'start_date', and 'end_date' are provided and formatted correctly."
    }
    ```

- **Error (401 Unauthorized):**

    **Response Body:**
    ```json
    {
        "message": "User is not authenticated."
    }
    ```

- **Error (404 Not Found):**

    **Response Body:**
    ```json
    {
        "message": "No answers found for the provided user ID and date range."
    }
    ```

**Notes:**
- Ensure that the `user_id`, `start_date`, and `end_date` query parameters are included and correctly formatted.
- The `start_date` and `end_date` should be in the format `YYYY-MM-DD` and must define a valid date range.
- Only authenticated users can access this endpoint.
- If no answers are found within the specified date range for the given user ID, a `404 Not Found` response will be returned.

**Functionality:**
- Validate the query parameters for correctness.
- Check that the user is authenticated.
- Retrieve and return all answers for the specified user within the given date range.
- Return appropriate error messages if parameters are invalid or if no answers are found.
