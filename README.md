![](https://i.ibb.co/93nmVqsD/awards.jpg)

![License](https://img.shields.io/badge/license-MIT-blue.svg) [![Latest Stable Version](https://img.shields.io/packagist/v/huseyinfiliz/awards.svg)](https://packagist.org/packages/huseyinfiliz/awards) [![Total Downloads](https://img.shields.io/packagist/dt/huseyinfiliz/awards.svg)](https://packagist.org/packages/huseyinfiliz/awards)

# Awards

A comprehensive community awards and voting extension for [Flarum](https://flarum.org) forums. Create annual awards, organize categories with nominees, let your community vote, and publish results with beautiful winner badges.

**Credits:** This extension sponsored by [@StryGuardian](https://discuss.flarum.org/u/stryguardian)

### Voting Interface
![Voting Demo](https://i.ibb.co/1f6Nhpzm/image.png)

### Results & Winners
![Results Demo](https://i.ibb.co/S4z8JmLQ/image.png)

### My Votes Dashboard
![My Votes Demo](https://i.ibb.co/rGrLHJJg/image.png)

### Admin Management
![Admin Demo](https://i.ibb.co/Hpt4113G/image.png)

## Features

- **Award Ceremonies**: Create annual or event-based awards (e.g., "Game Awards 2025", "Community Choice")
- **Categories & Nominees**: Organize awards into categories with images and descriptions
- **Flexible Voting**: Single vote (replace), multiple votes per category, or unlimited voting
- **User Suggestions**: Let users suggest nominees with admin approval workflow
- **Live Vote Counts**: Optionally show real-time vote counts during voting period
- **Notifications**: Automatic alerts when results are published to all voters
- **Winner Badges**: Gold, silver, and bronze badges for top 3 nominees
- **Hero Section**: Beautiful cover images with countdown timer
- **Responsive Design**: Card-based layout optimized for all devices
- **Prediction Summary**: Track your prediction accuracy after results are published
- **Auto-End Voting**: Automatic status updates based on start/end dates
- **Vote Adjustment**: Admin can adjust displayed vote counts when needed

## Compatibility

| Extension Version | Flarum Version |
|---|---|
| ^2.0.0 | ^2.0.0 |
| ^1.0.0 | ^1.8.0 |

## Installation

```bash
composer require huseyinfiliz/awards:"*"
```

You can also install with Extension Manager: `huseyinfiliz/awards`

## Updating

```bash
composer update huseyinfiliz/awards
php flarum migrate
php flarum cache:clear
```

## Quick Start

### For Users

1. Navigate to the **Awards** page from the sidebar
2. Browse categories and click on a **nominee card** to vote
3. Visit **My Votes** tab to track your voting progress
4. Use **Suggest Other** option where the category allows it
5. View **Results** after voting ends to see winners and your prediction score

### For Admins

Navigate to **Admin > Awards** to configure the system. The admin panel is divided into tabs for easy management:

#### Awards Tab

- Create awards with **Name**, **Year**, and **Voting Period**
- Set cover images for the hero section (URL or upload via FoF Upload)
- Control award status: Draft > Active > Ended > Published
- Toggle **Show Live Votes** for real-time vote counts during voting
- **Publish Results** to notify all voters when ready

#### Categories Tab

- Add categories to awards (e.g., "Best RPG", "Game of the Year")
- Enable **Allow User Suggestions** for community input
- Reorder categories with drag controls
- Set descriptions to guide voters

#### Nominees Tab

- Add nominees with **Name**, **Description**, and **Image**
- Supports image upload (requires FoF Upload) or external URLs
- **Vote Adjustment**: Manually adjust displayed vote counts (+/-)
- Reorder nominees within categories

#### Suggestions Tab

- Review pending user suggestions
- **Approve**: Create as new nominee (user automatically votes for it)
- **Reject**: Decline the suggestion
- **Merge**: Combine with existing nominee (user's vote transfers)

#### Settings Tab

- **Navigation Title**: Customize sidebar text
- **Navigation Icon**: Set FontAwesome icon class
- **Votes Per Category**: `0` = unlimited, `1` = single vote (replace), `N` = max N votes

## Award Status Flow

```
Draft > Active > Ended > Published
```

| Status | Description |
|--------|-------------|
| **Draft** | Setting up the award (only visible to admins for preview) |
| **Active** | Voting is open between start and end dates |
| **Ended** | Voting closed, admin reviews before publishing |
| **Published** | Results visible to everyone, all voters notified |

## Voting Modes

| Mode | Setting | Behavior |
|------|---------|----------|
| **Single Vote** | `1` | One vote per category, voting again replaces previous vote |
| **Multi-Vote** | `2-99` | Up to N votes per category, cannot change after voting |
| **Unlimited** | `0` | No limit on votes per category |

> **Note**: Pending suggestions count toward the vote quota in limited modes.

## Permissions

| Permission | Description |
|------------|-------------|
| **View Awards** | Access the awards page |
| **Vote in Awards** | Cast votes for nominees |
| **View Results Early** | See results before publishing (for moderators) |
| **Manage Awards** | Full admin access to create/edit/delete |

## Translations

This extension comes with English translations. Community translations are welcome!

## Support & Contributing

- Leaving feedback on the [Flarum discussion](https://discuss.flarum.org/d/38654-awards-community-voting-extension)
- Reporting issues on [GitHub](https://github.com/huseyinfiliz/awards/issues)
- Contributing translations

## License

MIT License - see [LICENSE.md](LICENSE.md)

---

Developed by [Huseyin Filiz](https://github.com/huseyinfiliz)
