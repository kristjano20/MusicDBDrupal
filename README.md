# TonlistarSkraning
**T-430-TOVH – Haustönn 2025**


## Authors
- Lára Amelía Kowalczyk
- Kristján Werner Óskarsson
  - Krummi Poomi Gunnarsson



---
## Installation Instructions

### Requirements

- **WSL (if running on Windows)**
- **DDEV**
- **Drupal 11**

---

### Setup Steps

1. Obtain the project files
   - Either clone the repository using Git
   - Or download the project as a ZIP file and extract it


2. Open a terminal in the project root directory
   - On Windows, use WSL
   - On macOS or Linux, use the terminal


3. Start the DDEV environment
    - install ddev if need
    - "ddev start" on the terminal


4. Install PHP dependencies
    - "ddev composer install" on terminal


5. Install Drupal (if not already installed)
   -  "ddev drush site:install" on terminal


6. Enable custom modules
   - "ddev drush en music_search spotify_lookup discogs_lookup music_db -y" on terminal


7. Clear Drupal caches
    - "ddev drush cr" on terminal


8. Launch Drupal
    - "ddev launch" on terminal
    - ("ddev drush uli" if you need admin access)

---

## Project Description

This project is a Drupal-based music database application that allows users to search for and manage music related content such as **Artists, Albums, and Songs**.

The main implementation is for **modular music search service** that integrates with multiple external APIs:

- Spotify
- Discogs

The system is designed so that:
- Each external music provider is implemented as its own Drupal module
- A shared MusicSearchService acts as a unified interface between the application and external APIs
- Music content such as Artists, Albums, and Songs can be created, viewed, and removed through custom Drupal entities
- Data for these entities can be entered manually or retrieved from external APIs such as Spotify and Discogs
- Autocomplete functionality is used to assist users in selecting correct music data from external sources

---

## Usage

**Creating Custom Entity**
- Navigate to Music Database then Music Search
- Select the Type you want to search for (Artist, Album or Song)
- Type in the desired title or name. A list of options should appear (data from spotify and discogs)
- Select the desired content and proceed to the next page.
- Select the desired data you would like to use between the providers (images, informations, descriptions, IDs, etc )
- Fill any information that you desire
- Save


Congrats! You have now created your custom entity.

**View your created contents**
- Navigate through Music Database and then "View Artist", "View Albums" or "View Songs" to find your created contents

---
