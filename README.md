# ownNotes (alpha)
This application is a modified verion of ownNote.

Changes:
- Modified to work up to Nextcloud 13
- Replaced deprecated methods
- Removed XSS vulnerability (Via the announcements it was possible to inject javascript / html)
- Fixed sharing.
- Fixed CSP error in tinymce.
 
 
# Compatibility
Fully compatible with ownNotes


# Features
- Import from Evernote
- Full fledged WYSIWYG editor
- Ability to save files to a folder as HTML files (untested)
- Share a note with a user or group
- Note grouping/categorization

Todo:
- [ ] Import from [Notes](https://github.com/nextcloud/notes) app
- [ ] Encrypted notes? 
- [ ] Test saving of files

## Installation
- Place this app in **nextcloud/apps/ownnote** (Rename the extracted ZIP to "ownnote" or you will receive errors)
- Note: *custom_csp_policy* changes are no longer required

## Mobile Apps
ownNote for Android in the Google Play store: http://goo.gl/pHjQY9

ownNote for iOS: *In development*

# Based on
- [ownNote](https://github.com/Fmstrat/ownnote)
- Sharing done by [nalch](https://github.com/nalch)