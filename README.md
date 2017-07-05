# ownNote in transition to NextNote(alpha)
This application is a modified verion of ownNote.   
Currently it **will** not work, only the api is ready so far.
    
Changes:
- Modified to work up to Nextcloud 13
- Replaced deprecated methods
- Removed XSS vulnerability (Via the announcements it was possible to inject javascript / html)
- Fixed sharing.
- Updated tiny MCE to  4.6.4.
- Fixed CSP error in tinymce.
 
 
# Compatibility
Fully compatible with ownNote

# Pull requests are very welcome!
I've refactored the whole app, so there will be some bugs in there.   
Did you found a bug? Report it or fix it and send a PR.

# Features
- Import from Evernote
- Full fledged WYSIWYG editor
- Ability to save files to a folder as HTML files (untested)
- Share a note with a user or group
- Note grouping/categorization

Todo:
- [x] Refactor backend to make use of:
  - [x] Entity's
  - [x] Mappers
  - [x] Services
  - [ ] Hooks
- [ ] Switch to a AngularJS frontend        
- [ ] Import from [Notes](https://github.com/nextcloud/notes) app
- [ ] Encrypted notes? 
- [ ] Test saving of files   
   
## Chat
There is a [Gitter](https://gitter.im/nextnotes/Lobby) chatroom available.
   
## Installation
- Place this app in **nextcloud/apps/ownnote** (Rename the extracted ZIP to "ownnote" or you will receive errors)
- Note: *custom_csp_policy* changes are no longer required

## Mobile Apps
ownNote for Android in the Google Play store: http://goo.gl/pHjQY9

ownNote for iOS: *In development*

# Based on
- [ownNote](https://github.com/Fmstrat/ownnote)
- Sharing done by [nalch](https://github.com/nalch)