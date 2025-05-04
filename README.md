# City Explorer

## Access Information
- URL: https://ec2-3-64-57-232.eu-central-1.compute.amazonaws.com/
- Login: test@test.com
- Password: password

## Project Overview
City Explorer is a web application that allows users to explore cities, discover places of interest, check weather conditions, and save their favorite locations. The application integrates with external APIs to provide up-to-date information about places and weather.

## Features

### User Authentication
- User registration and login
- Profile management
- Password reset functionality

### City Exploration
- Search for places of interest near a specific location
- View detailed information about places
- Get detailed weather forecasts for any location
- Access information about local time and local currency

### Favorites Management
- Save favorite cities for quick access
- Save favorite places/points of interest
- View and manage saved favorites

### API Integration
- Integration with Foursquare API for place information
- Integration with OpenWeatherMap API for weather data
- Integration with MapBox API for maps and location services

## Architecture
The application follows a clean architecture approach with emphasis on maintainability, testability, and scalability:

- **Controllers**: Handle HTTP requests and responses, with minimal business logic
- **Services**: Implement business logic with low coupling through dependency injection
- **Models**: Define data structure and relationships
- **Data Transfer Objects (DTOs)**: Used for data transformation and validation between layers
- **Interfaces/Contracts**: Define clear contracts for dependency injection, enabling easy mocking for tests
- **Mappers**: Transform external API data to application format

Key architectural principles:
- **Dependency Injection**: All services are injected through interfaces, allowing for easy replacement and testing
- **Single Responsibility**: Each class has a single responsibility and reason to change
- **Low Coupling**: Services are loosely coupled through contracts, reducing dependencies
- **High Cohesion**: Related functionality is grouped together
- **Out-of-the-box Solutions**: Leveraging Laravel's built-in features and packages for common functionality

## Technical Stack
- **Backend Framework**: Laravel (PHP)
- **Database**: PostgreSQL
- **Caching**: Redis (configured with Laravel cache)
- **Containerization**: Docker
- **Deployment**: AWS ECS + Fargate
- **Authentication**: Laravel Breeze for session-based authentication
- **API Protection**: Laravel Sanctum for API route protection
- **DTOs**: Laravel Spatie package for creating Data Transfer Objects

## Deployment
The application is deployed on AWS using ECS + Fargate with containerized services.

## Development
For development and testing purposes, all terminal commands must be executed within the Docker container using the prefix:
```
docker compose exec app [command]
```

Example:
```
docker compose exec app php artisan cache:clear
```

## Installation

To set up the project locally:

1. Clone the repository
2. Rename `.env.example` to `.env`
3. Add the necessary values to the `.env` file
4. Run the following command from the project root:
   ```
   docker compose up
   ```

## Docker Environment

The project includes a comprehensive Docker environment with the following services:

- **PHP/Laravel Application**: The main application container
- **PostgreSQL**: Database server
- **Redis**: Caching server
- **Redis Commander**: Web interface for Redis management
- **Nginx**: Web server

## Testing

The project includes comprehensive integration tests to ensure functionality works as expected. To run the tests:

```
docker compose exec app php artisan test
```



## Security
The application implements standard security practices including:
- Authentication using Laravel's built-in mechanisms
- Input validation
- Error handling and logging
- API key management for external services
