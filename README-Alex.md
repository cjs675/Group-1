# COP 3350 - Resource Hub Website

## Overview

For our Systems Administration group project, I made a website that allows students to access all 21 lectures from class. The website runs on our RHEL 9.8 server using Apache, PHP, HTML, and CSS.

The main idea was to make it easier to find lectures instead of having to go through Canvas every time.

## Features

- Displays all 21 lectures as PDFs.
- Shows the actual lecture names instead of L01, L02, etc.
- Has a search bar to find specific lectures.
- Uses PHP to search lecture titles, keywords, and extracted PDF text.
- Allows users to open or download the lectures.
- Automatically displays the available PDF files.

## Files

**index.php** - Main website file. Contains the HTML and PHP code for displaying the lectures and searching through them.

**style.css** - Controls how the website looks, including the colors, layout, search bar, and lecture cards.

**lectures.csv** - Contains the lecture names and keywords used by the search feature.

**slides/** - Contains the 21 lecture PDFs.

## How It Works

The website reads the PDFs from the slides folder and displays them on the main page.

I used a CSV file to give each lecture its actual title and some keywords. I also used `pdftotext` to extract the text from the PDFs so the search can check their contents.

When someone searches for something, the PHP code checks the lecture information and displays the matching files.

## Setup

The website is currently running on our RHEL server at:

`https://resources.lab.test`

The main files are stored in `/var/www/resources/`, and the lecture information is stored in `/var/www/lecture-index/`.

The website requires Apache and PHP. The PDF text files also need to be generated using `pdftotext` for the full-text search to work.

## Testing

I tested the website from our RHEL client and confirmed that it loads over HTTPS. I also tested the original search feature and confirmed that it displays matching files and handles searches with no results.

The updated search using the lecture titles and extracted PDF text still needs final testing.

## My Contribution

I worked on creating the main Resource Hub website. I made the initial layout using HTML and CSS and used PHP for the lecture search.

I added the 21 lectures, organized their titles and keywords, and extracted their text for the updated search feature.

I also tested the original website and made changes to improve how the lectures are displayed and searched.

The DNS, Apache security, and backup portions are being worked on separately by the other group members.

## Notes

The lecture PDFs are included in our private GitHub repository with permission.

AI assistance was used for coding guidance and troubleshooting throughout development.
