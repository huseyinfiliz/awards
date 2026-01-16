![](https://i.ibb.co/PLACEHOLDER/awards-demo.gif)

![License](https://img.shields.io/badge/license-MIT-blue.svg) [![Latest Stable Version](https://img.shields.io/packagist/v/huseyinfiliz/awards.svg)](https://packagist.org/packages/huseyinfiliz/awards) [![Total Downloads](https://img.shields.io/packagist/dt/huseyinfiliz/awards.svg)](https://packagist.org/packages/huseyinfiliz/awards)

# Awards

A comprehensive community awards and voting extension for [Flarum](https://flarum.org) forums. Create annual awards, let your community vote for nominees, and publish results with beautiful winner badges.

## Features

- 🏆 **Award Ceremonies**: Create annual or event-based awards (e.g., "Game Awards 2025", "Community Choice")
- 📂 **Categories & Nominees**: Organize awards into categories with images and descriptions
- 🗳️ **Flexible Voting**: Single vote, multiple votes, or unlimited voting per category
- 💡 **User Suggestions**: Let users suggest nominees (with admin approval)
- 📊 **Live Vote Counts**: Optionally show real-time vote counts during voting
- 🔔 **Notifications**: Automatic alerts when results are published
- 🥇 **Winner Badges**: Gold, silver, and bronze badges for top 3 nominees
- 📱 **Responsive Design**: Beautiful card-based layout optimized for all devices

### 🗳️ Voting Interface
![Voting Demo](https://i.ibb.co/PLACEHOLDER/voting.png)

### 🏆 Results & Winners
![Results Demo](https://i.ibb.co/PLACEHOLDER/results.png)

### 📊 My Votes Dashboard
![My Votes Demo](https://i.ibb.co/PLACEHOLDER/my-votes.png)

### ⚙️ Admin Management
![Admin Demo](https://i.ibb.co/PLACEHOLDER/admin.png)

## Installation

```bash
composer require huseyinfiliz/awards:"*"
```

You can also install with Extension Manager: `huseyinfiliz/awards`

## Updating

```sh
composer update huseyinfiliz/awards
```

To remove simply run `composer remove huseyinfiliz/awards`

## Quick Start

### For Users

1. Navigate to the **Awards** page from the sidebar
2. Browse categories and click **Select** to vote for your favorite
3. Visit **My Votes** tab to track your voting progress
4. Suggest nominees where the category allows it
5. View **Results** after voting ends to see the winners

### For Admins

Navigate to **Admin → Awards** to configure the system:

#### Awards Tab
- Create awards with name, year, and voting period
- Set cover images for the hero section
- Control award status: Draft → Active → Ended → Published
- Toggle "Show Live Votes" for real-time counts
- Publish results to notify all voters

#### Categories Tab
- Add categories to awards (e.g., "Best RPG", "Game of the Year")
- Enable "Allow User Suggestions" for community input
- Drag to reorder categories

#### Nominees Tab
- Add nominees with name, description, and image
- Adjust vote counts manually when needed
- Supports image upload (requires FoF Upload) or external URLs

#### Suggestions Tab
- Review pending user suggestions
- Approve to create as new nominee
- Reject or merge into existing nominee

#### Settings Tab
- **Navigation Title**: Customize sidebar text
- **Votes Per Category**: 0 = unlimited, 1 = single vote, N = max N votes

## Award Status Flow

```
Draft → Active → Ended → Published
```

| Status | Description |
|--------|-------------|
| **Draft** | Setting up the award (not visible to users) |
| **Active** | Voting is open between start and end dates |
| **Ended** | Voting closed, admin reviews before publishing |
| **Published** | Results visible, all voters notified |

## Permissions

| Permission | Description |
|------------|-------------|
| View Awards | Access the awards page |
| Vote in Awards | Cast votes for nominees |
| View Results Early | See results before publishing |
| Manage Awards | Full admin access |

## 🌍 Translations

This extension comes with English translations. Community translations are welcome!

## 💖 Support & Contributing

If you find this extension useful, consider:

- ⭐ Starring the repository on GitHub
- 💬 Leaving feedback on the [Flarum discussion](https://discuss.flarum.org)
- 🐛 Reporting issues on [GitHub](https://github.com/huseyinfiliz/awards/issues)

## License

MIT License - see [LICENSE.md](LICENSE.md)

---

Developed with ❤️ by [Huseyin Filiz](https://github.com/huseyinfiliz)
