# Awards

![License](https://img.shields.io/badge/license-MIT-blue.svg) [![Latest Stable Version](https://img.shields.io/packagist/v/huseyinfiliz/awards.svg)](https://packagist.org/packages/huseyinfiliz/awards) [![Total Downloads](https://img.shields.io/packagist/dt/huseyinfiliz/awards.svg)](https://packagist.org/packages/huseyinfiliz/awards)

A community awards and voting extension for [Flarum](https://flarum.org) forums. Create annual awards, let your community vote for nominees, and publish results with winner badges.

## Features

- **Create Awards**: Set up annual or event-based awards (e.g., "Game Awards 2025", "Community Choice Awards")
- **Categories & Nominees**: Organize awards into categories with multiple nominees each
- **Voting System**: Users vote for their favorite nominees with configurable vote limits
- **Vote Management**: Single vote (replace), multiple votes, or unlimited voting per category
- **User Suggestions**: Allow users to suggest nominees (with admin approval)
- **Live Vote Counts**: Optionally show vote counts during active voting
- **Results Publishing**: Publish results with automatic notifications to voters
- **Winner Badges**: Display gold/silver/bronze badges for top 3 nominees
- **Admin Vote Editing**: Manually adjust vote counts when needed
- **Rate Limiting**: Built-in protection against vote spam (10 votes/minute)

## Screenshots

<!-- Add your screenshots here -->
*Screenshots coming soon*

## Installation

Install via Composer:

```bash
composer require huseyinfiliz/awards:"*"
```

Or install using Extension Manager: `huseyinfiliz/awards`

## Updating

```bash
composer update huseyinfiliz/awards
php flarum migrate
php flarum cache:clear
```

## Removal

```bash
composer remove huseyinfiliz/awards
```

## Configuration

### Admin Panel

Navigate to **Admin -> Awards** to manage your awards:

#### Awards Tab
- Create awards with name, year, description, and dates
- Set award status: Draft -> Active -> Ended -> Published
- Toggle "Show Live Votes" to display counts during voting
- Publish results to notify all voters

#### Categories Tab
- Add categories to awards (e.g., "Best RPG", "Game of the Year")
- Enable "Allow User Suggestions" for community input
- Set sort order for display

#### Nominees Tab
- Add nominees with name and image URL
- View and edit vote counts
- Filter by award and category

#### Suggestions Tab
- Review pending user suggestions
- Approve (creates new nominee)
- Reject or merge into existing nominee

#### Settings Tab
- **Navigation Title**: Text shown in sidebar (default: "Awards")
- **Votes Per Category**:
  - `0` = Unlimited votes
  - `1` = Single vote (clicking another replaces previous)
  - `N` = Maximum N votes per category

### Permissions

Configure in **Admin -> Permissions**:

| Permission | Description |
|------------|-------------|
| View Awards | Access the awards page |
| Vote in Awards | Cast votes for nominees |
| View Results Early | See results before publishing |
| Manage Awards | Full admin access |

## Award Status Flow

```
Draft -> Active -> Ended -> Published
```

1. **Draft**: Admin is setting up the award (not visible to users)
2. **Active**: Voting is open (between start and end dates)
3. **Ended**: Voting closed, admin reviews before publishing
4. **Published**: Results visible, all voters receive notifications

## User Guide

### Voting
1. Navigate to the Awards page from the sidebar
2. Select an award (if multiple are active)
3. Browse categories and click "Select" on your choice
4. Click "Voted" to remove your vote
5. Suggest nominees where allowed (pending admin approval)

### Viewing Results
- Published awards show winners with badges
- Gold for 1st place, Silver for 2nd, Bronze for 3rd
- Vote counts and percentages displayed

## Technical Details

### Database Tables
- `awards` - Main award records
- `award_categories` - Categories within awards
- `award_nominees` - Nominees in categories
- `award_votes` - User votes
- `award_other_suggestions` - User-submitted suggestions

### API Endpoints
- `GET/POST /api/awards` - List/create awards
- `GET/PATCH/DELETE /api/awards/{id}` - Show/update/delete award
- `POST /api/awards/{id}/publish` - Publish results
- Similar CRUD for categories, nominees, votes, suggestions

## Translations

This extension includes English translations. Community translations welcome!

## Support

- **Issues**: [GitHub Issues](https://github.com/huseyinfiliz/awards/issues)
- **Discussion**: [Flarum Discuss](https://discuss.flarum.org)

## License

MIT License - see [LICENSE.md](LICENSE.md)

## Credits

Developed by [Huseyin Filiz](https://github.com/huseyinfiliz)
