# TonlistarSkraning
**T-430-TOVH – Haustönn 2025**


## Authors
- Kristján Werner Óskarsson
- Krummi Poomi Gunnarsson
- Lára Amelía Kowalczyk


---
## Installation Instructions

### Requirements

- **WSL (if running on Windows)**
- **DDEV**
- **Drupal 11**

---

### Setup Steps

1. Obtain the module files and place them in the correct folder.
   - Usually your_project/web/modules/custom

2. Install the module either with drush or on the site
   - Drush: ddev drush en music_db
   - On the site: go to extend, find music_db and install it

3. Once the module is installed go to the site and go into Configuration -> Web services
   - Go into Spotify API module settings and add your Client ID and Client Secret
   - Go into Discocs API module settings and add your API Token.

4. Now the module is installed and you can start using it.
---

---

## Project Description

This project is a Drupal-based music database application that allows users to search for and manage music related content such as **Artists, Albums, and Songs**.

The main implementation is for **modular music search and data retrieval service** that integrates with multiple external APIs.
In the current version the following API's are integrated:

- Spotify
- Discogs

The system is designed so that:
- Each external music provider is implemented as its own Drupal module
- A shared MusicSearchService acts as a unified interface between the application and external APIs
- Music content such as Artists, Albums, and Songs can be created, viewed, and removed through custom Drupal entities
- Data for these entities can be entered manually or retrieved from external APIs such as Spotify and Discogs


---

## Usage

**Creating Custom Entity**
- Navigate to Music Database then Music Search or go the url /music-search.
- Select the Type you want to search for (Artist, Album or Song)
- Type in the desired title or name. For albums you currently have to enter a artist name along with a album search string.
- A list of options should appear (data from spotify and discogs)
- Select the desired content and proceed to the next page.
- Select the desired data you would like to use between the providers (images, informations, descriptions, IDs, etc )
- Choose which data you want to store to the database.
- Save


Congrats! You have now created your custom entity.

**View your created contents**
- Navigate through Music Database and then "View Artist", "View Albums" or "View Songs" to find your created contents
- The urls for the views are:
  - admin/content/artists
  - admin/content/albums
  - admin/content/songs

---

## Next steps

- Add the option to edit the entities that have been created
- Add autocomplete for the fields where searches are performed
- When searching for a song, add an artist name and album behind the song title when choosing
- Add references between entitites Artists -> Albums -> Songs
- Add referenced taxonomy terms for genres and create them if they don't exist
- Currently when adding songs we are only searching spotify we want to add functionality where once you have selected a
 from spotify the corresponding song is found on discogs and the discogs id passed along to the data selection step.
- When adding a album with a song list if the songs could automatically be created including a reference to the album.
- Add logging support for all actions
- Perhaps make the entities revisionable, unsure about this.
