# Rugby Referee Designation Management Application - Architecture Plan

## Overview
This application manages the designation of rugby referees to matches by a designatore (assignor). It handles matches, referees, teams, and the assignment process.

## Core Entities

### 1. Referee
- id (primary key)
- name
- email
- phone
- license_level (e.g., National, Regional, International)
- availability_status
- created_at
- updated_at

### 2. Team
- id (primary key)
- name
- city
- league/division
- contact_person
- contact_email
- contact_phone
- created_at
- updated_at

### 3. Match
- id (primary key)
- date_time
- venue
- home_team_id (foreign key to teams)
- away_team_id (foreign key to teams)
- competition_type (league, cup, friendly, etc.)
- status (scheduled, postponed, cancelled, completed)
- created_at
- updated_at

### 4. Designation (Assignment)
- id (primary key)
- match_id (foreign key to matches)
- referee_id (foreign key to referees)
- assigned_by (user who made the assignment)
- assignment_date
- status (pending, confirmed, completed, cancelled)
- notes
- created_at
- updated_at

### 5. User (extends Laravel's built-in User)
- id (primary key)
- name
- email
- password
- role (designatore, admin, referee)
- remember_token
- created_at
- updated_at

## Relationships

- Referee hasMany Designation
- Team hasMany Match (as home_team and away_team)
- Match belongsTo Team (home_team)
- Match belongsTo Team (away_team)
- Match hasOne Designation
- Designation belongsTo Match
- Designation belongsTo Referee
- Designation belongsTo User (assigned_by)
- User hasMany Designation (as assigned_by)

## Key Features

1. Referee Management
   - CRUD operations for referees
   - License level tracking
   - Availability management

2. Team Management
   - CRUD operations for teams
   - League/division tracking

3. Match Management
   - Schedule matches
   - Track match status
   - Venue management

4. Designation/Assignment System
   - Assign referees to matches
   - Track assignment status
   - Notification system (email)
   - Conflict detection (double booking)

5. Reporting
   - Upcoming matches with assigned referees
   - Referee workload statistics
   - Assignment history

## Technical Architecture

### Backend
- Laravel 13 PHP framework
- MySQL/SQLite database
- RESTful API controllers
- Eloquent ORM
- Blade templating engine
- Laravel Sanctum for API authentication (if needed)
- Laravel Queue for notifications
- Laravel Events for assignment updates

### Frontend
- Blade templates with TailwindCSS
- Alpine.js for interactive components
- Responsive design

### Database
- SQLite for development (as per existing setup)
- Migration-based schema
- Factories and seeders for testing

## Security Considerations
- Authentication and authorization
- Input validation
- CSRF protection
- Secure password handling
- Role-based access control

## API Endpoints (if building API)
- GET /api/referees
- POST /api/referees
- GET /api/referees/{id}
- PUT /api/referees/{id}
- DELETE /api/referees/{id}
- Similar endpoints for teams, matches, designations
- GET /api/dashboard/stats

## Testing Strategy
- Unit tests for models and services
- Feature tests for HTTP endpoints
- Pest testing framework
- Factory model factories for test data

## Deployment
- Laravel Herd for local development
- Environment configuration via .env
- Queue worker for background jobs
- Supervisor for queue management in production
