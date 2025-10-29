# 🌉 CharityBridge

<div align="center">

![CharityBridge](https://img.shields.io/badge/CharityBridge-Connecting_Hearts-blue?style=for-the-badge)
![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

**Connecting Hearts, Changing Lives**

A comprehensive web platform that bridges the gap between NGOs, donors, volunteers, and restaurants to create meaningful social impact.

[Features](#-features) • [Demo](#-demo) • [Installation](#-installation) • [Documentation](#-documentation) • [Contributing](#-contributing)

</div>

---

## 📋 Table of Contents

- [About](#-about)
- [Key Features](#-key-features)
- [Technology Stack](#-technology-stack)
- [System Architecture](#-system-architecture)
- [File Structure](#-file-structure)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Usage](#-usage)
- [User Roles](#-user-roles)
- [Screenshots](#-screenshots)
- [API Documentation](#-api-documentation)
- [Security](#-security)
- [Contributing](#-contributing)
- [License](#-license)
- [Contact](#-contact)

---

## 🎯 About

CharityBridge is a modern, full-stack web application designed to streamline charitable operations by connecting multiple stakeholders in a unified ecosystem. The platform facilitates seamless collaboration between:

- **NGOs** seeking donations and volunteers
- **Donors** wanting to contribute money or goods
- **Volunteers** looking to offer their time and skills
- **Restaurants** donating surplus food
- **Administrators** managing and verifying organizations

### 🌟 Why CharityBridge?

- 🔗 **Unified Platform**: Single ecosystem for all charity stakeholders
- 🎯 **Smart Matching**: Connects needs with resources efficiently
- 📊 **Real-time Tracking**: Track donations and volunteer activities
- 🔒 **Secure & Verified**: Admin approval system for NGOs and restaurants
- 📱 **Responsive Design**: Works seamlessly on all devices
- 🍽️ **Food Waste Reduction**: Restaurants can donate surplus food
- 💰 **Multiple Payment Options**: Community Driven

---

## ✨ Key Features

### For NGOs 🏢
- ✅ Organization registration with Darpan ID verification
- ✅ Post requirements for goods, clothes, and volunteers
- ✅ Accept and manage donations (goods, clothes)
- ✅ Request and manage volunteers for events
- ✅ Track donation history and generate reports
- ✅ Manage volunteer applications and approvals
- ✅ Accept food donations from restaurants

### For Donors ❤️
- ✅ Browse verified NGOs and their requirements
- ✅ Coordinate directly with NGOs for donations
- ✅ Donate goods and clothes with pickup scheduling
- ✅ Track donation status in real-time
- ✅ View donation history
- ✅ Profile management

### For Volunteers 🤝
- ✅ Browse volunteer opportunities by location and skills
- ✅ Apply for opportunities matching expertise
- ✅ Manage schedule and commitments
- ✅ Logistics management for goods/food pickups
- ✅ OTP-based pickup verification system
- ✅ Track volunteer hours and impact

### For Restaurants 🍽️
- ✅ Register with FSSAI license verification
- ✅ Post surplus food donations
- ✅ Specify quantity, type, and pickup time
- ✅ Track food donation history
- ✅ OTP verification for food pickups
- ✅ Reduce food waste and help community

### For Administrators 👨‍💼
- ✅ Approve/reject NGO registrations
- ✅ Verify restaurant licenses
- ✅ Monitor platform activities
- ✅ Generate comprehensive reports
- ✅ Manage user accounts
- ✅ View analytics and statistics

---

## 🛠️ Technology Stack

### Frontend
- **HTML5** - Semantic markup
- **CSS3** - Modern styling with flexbox/grid
- **JavaScript (ES6+)** - Interactive functionality
- **Tailwind CSS** - Utility-first CSS framework
- **Font Awesome** - Icon library

### Backend
- **PHP 7.4+** - Server-side scripting
- **MySQL 8.0** - Relational database
- **Session Management** - Secure user authentication

### Third-Party Integrations

- **SMS Gateway** - OTP and notifications


### Development Tools
- **XAMPP** - Local development environment
- **Git** - Version control
- **VSCode** - Code editor

---

## 🏗️ System Architecture

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Frontend  │────▶│   Backend   │────▶│  Database   │
│  (HTML/CSS/ │     │    (PHP)    │     │   (MySQL)   │
│     JS)     │◀────│             │◀────│             │
└─────────────┘     └─────────────┘     └─────────────┘
       │                    │                    │
       │                    ▼                    │
       │            ┌───────────────┐            │
       └───────────▶│  Third-party  │◀───────────┘
                    │   Services    │
                    │ (SMS)│
                    └───────────────┘
```

### Data Flow
1. **User Interface** → User interacts with responsive web pages
2. **PHP Backend** → Processes requests, validates data
3. **MySQL Database** → Stores and retrieves data
4. **External APIs** → Handles notifications

---

## 📁 File Structure

```
CharityBridge2/
│
├── 📂 admin/                        # Admin Portal
│   ├── 📂 backend/                  # Admin backend scripts
│   │   ├── approve_ngo.php
│   │   ├── approve_restaurant.php
│   │   ├── generate_reports.php
│   │   └── logout.php
│   ├── dashboard.php                # Admin dashboard
│   ├── approvals.php                # NGO/Restaurant approvals
│   └── login.php                    # Admin login
│
├── 📂 css/                          # Stylesheets
│   ├── style.css                    # Main stylesheet
│   └── dashboard.css                # Dashboard styles
│
├── 📂 donor/                        # Donor Portal
│   ├── 📂 backend/                  # Donor backend scripts
│   │   ├── donor_login.php
│   │   ├── donor_register.php
│   │   ├── process_donation.php
│   │   └── donor_logout.php
│   ├── dashboard.php                # Donor dashboard
│   ├── donate.php                   # Browse NGOs for donation
│   ├── donate_form.php              # Donation form
│   ├── tracking.php                 # Track donations
│   └── manage_profile.php           # Profile management
│
├── 📂 docs/                         # Documentation
│   └── (Development documentation)
│
├── 📂 includes/                     # Shared utilities
│   ├── config.php                   # Database configuration
│   ├── sms_helper.php               # SMS integration
│   └── SMS_SETUP_README.md          # SMS setup guide
│
├── 📂 js/                           # JavaScript files
│   ├── animations.js                # Page animations
│   ├── session_manager.js           # Session handling
│   ├── dynamic_updates.js           # Real-time updates
│   ├── script.js                    # General scripts
│   ├── ngo_script.js                # NGO-specific scripts
│   └── admin.js                     # Admin scripts
│
├── 📂 ngo/                          # NGO Portal
│   ├── 📂 backend/                  # NGO backend scripts
│   │   ├── ngo_login.php
│   │   ├── ngo_register.php
│   │   ├── process_donation.php
│   │   ├── accept_food_donation.php
│   │   └── logout.php
│   ├── 📂 certificates/             # Uploaded NGO certificates
│   │   └── .gitkeep
│   ├── 📂 pages/                    # NGO pages
│   │   ├── dashboard.php            # NGO dashboard
│   │   ├── requirements.php         # Post requirements
│   │   ├── donations.php            # View donations
│   │   ├── volunteers.php           # Manage volunteers
│   │   ├── food_donations.php       # Food donations
│   │   └── profile.php              # NGO profile
│   └── 📂 utils/                    # NGO utilities
│
├── 📂 restaurant/                   # Restaurant Portal
│   ├── 📂 backend/                  # Restaurant backend
│   │   ├── restaurant_login.php
│   │   ├── restaurant_register.php
│   │   ├── process_food_post.php
│   │   ├── cancel_post.php
│   │   └── 📂 licenses/             # Uploaded licenses
│   │       └── .gitkeep
│   └── 📂 pge/                      # Restaurant pages
│       ├── dashboard.php            # Restaurant dashboard
│       ├── post_food.php            # Post food donation
│       ├── my_donations.php         # View donations
│       ├── history.php              # Donation history
│       ├── profile.php              # Restaurant profile
│       ├── dashboard.css            # Restaurant styles
│       └── 📂 uploads/              # Food images
│           └── .gitkeep
│
├── 📂 volunteer/                    # Volunteer Portal
│   ├── 📂 backend/                  # Volunteer backend
│   │   ├── volunteer_login.php
│   │   ├── volunteer_register.php
│   │   ├── apply_opportunity.php
│   │   ├── withdraw_application.php
│   │   ├── process_volunteer_action.php
│   │   └── logout.php
│   └── 📂 pages/                    # Volunteer pages
│       ├── dashboard.php            # Volunteer dashboard
│       ├── opportunities.php        # Browse opportunities
│       ├── scheduler.php            # Manage schedule
│       ├── logistics.php            # Pickup logistics
│       └── manage_profile.php       # Profile management
│
├── 📄 index.html                    # Landing page
├── 📄 login.html                    # Unified login page
├── 📄 register.html                 # Unified registration
├── 📄 charitybridge.sql             # Database schema
│
├── 📄 .gitignore                    # Git ignore rules
├── 📄 README.md                     # This file
├── 📄 LICENSE                       # MIT License
│
└── 📄 Documentation Files
    ├── COLOR_SCHEME_UNIFIED.md      # Design system
    ├── PRODUCTION_READINESS.md      # Production checklist
    ├── PAYMENT_SETUP.md             # Payment integration
    ├── QR_PAYMENT_SETUP.md          # QR payment guide
    └── CLEANUP_SUMMARY.md           # Cleanup documentation
```

---

## 🚀 Installation

### Prerequisites

- **XAMPP** (or LAMP/WAMP)
  - PHP 7.4 or higher
  - MySQL 8.0 or higher
  - Apache 2.4
- **Web Browser** (Chrome, Firefox, Safari, or Edge)
- **Git** (for cloning the repository)

### Step-by-Step Installation

#### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/CharityBridge2.git
cd CharityBridge2
```

#### 2. Move to XAMPP Directory

```bash
# Windows
move CharityBridge2 C:\xampp\htdocs\

# Linux/Mac
sudo mv CharityBridge2 /opt/lampp/htdocs/
```

#### 3. Create Database

1. Start XAMPP (Apache + MySQL)
2. Open phpMyAdmin: `http://localhost/phpmyadmin`
3. Create a new database: `charitybridge`
4. Import the SQL file:
   - Click on `charitybridge` database
   - Go to **Import** tab
   - Choose file: `charitybridge.sql`
   - Click **Go**

#### 4. Configure Database Connection

Edit database credentials in PHP files if needed (default is localhost/root/no password):

```php
// Most files use:
$conn = new mysqli('localhost', 'root', '', 'charitybridge');
```

#### 5. Set Up Payment Gateway (Optional)

See `PAYMENT_SETUP.md` for detailed Razorpay integration instructions.

#### 5. Configure SMS Gateway (Optional)

See `includes/SMS_SETUP_README.md` for SMS service setup.

#### 6. Set Permissions (Linux/Mac)

```bash
sudo chmod -R 755 /opt/lampp/htdocs/CharityBridge2
sudo chmod -R 777 /opt/lampp/htdocs/CharityBridge2/ngo/certificates
sudo chmod -R 777 /opt/lampp/htdocs/CharityBridge2/restaurant/backend/licenses
sudo chmod -R 777 /opt/lampp/htdocs/CharityBridge2/restaurant/pge/uploads
```

#### 7. Access the Application

Open your browser and navigate to:

```
http://localhost/CharityBridge2/
```

---

## ⚙️ Configuration

### Database Configuration

The default database configuration is:

```php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "charitybridge";
```

### Payment Gateway

1. Sign up for a Razorpay account
2. Get your API keys (Key ID and Secret)
3. Update payment files with your credentials
4. See `PAYMENT_SETUP.md` for detailed instructions

### SMS Gateway

1. Choose an SMS provider (TextLocal, Twilio, etc.)
2. Get API credentials
3. Configure in `includes/sms_helper.php`
4. See `includes/SMS_SETUP_README.md` for setup

---

## 📖 Usage

### Default Admin Credentials

```
Username: admin
Password: admin123
```

**⚠️ IMPORTANT: Change these credentials after first login!**

### User Registration Flow

1. **NGOs & Restaurants**
   - Register through respective portals
   - Upload verification documents
   - Wait for admin approval
   - Login after approval

2. **Donors & Volunteers**
   - Register directly
   - Verify email (if configured)
   - Login immediately

### Typical Workflows

#### Donation Workflow
```
Donor → Browse NGOs → Select Requirement → Donate → Track Status
```

#### Volunteer Workflow
```
Volunteer → Browse Opportunities → Apply → Get Approved → Complete Task
```

#### Food Donation Workflow
```
Restaurant → Post Food → NGO Accepts → Volunteer Picks Up → Deliver
```

---

## 👥 User Roles

| Role | Access Level | Key Functions |
|------|--------------|---------------|
| **Admin** | Full System | Approve organizations, monitor activities, generate reports |
| **NGO** | Organization Portal | Post requirements, accept donations, manage volunteers |
| **Donor** | Donation Portal | Browse NGOs, make donations, track contributions |
| **Volunteer** | Volunteer Portal | Browse opportunities, manage schedule, handle logistics |
| **Restaurant** | Restaurant Portal | Post surplus food, track donations, manage pickups |

---

## 📸 Screenshots

### Landing Page
![Landing Page](screenshots/landing.png)

### NGO Dashboard
![NGO Dashboard](screenshots/ngo-dashboard.png)

### Donation Page
![Donation Page](screenshots/donation.png)

### Volunteer Opportunities
![Volunteer Opportunities](screenshots/volunteer-opportunities.png)

---

## 🔐 Security

### Current Security Features
- ✅ Session-based authentication
- ✅ HTML escaping to prevent XSS
- ✅ Role-based access control
- ✅ Admin verification for organizations
- ✅ OTP verification for pickups

### Security Recommendations (Before Production)

⚠️ **CRITICAL - Must implement before deploying:**

1. **Password Security**
   - Replace MD5 hashing with `password_hash()` (bcrypt)
   - Implement password strength requirements

2. **SQL Injection Prevention**
   - Convert all queries to prepared statements
   - Validate and sanitize all user inputs

3. **CSRF Protection**
   - Add CSRF tokens to all forms
   - Validate tokens on submission

4. **File Upload Security**
   - Validate file types and sizes
   - Rename uploaded files
   - Store outside web root

5. **Environment Variables**
   - Move database credentials to .env file
   - Use environment-specific configurations

6. **HTTPS**
   - Enable SSL/TLS
   - Force HTTPS for all pages

See `PRODUCTION_READINESS.md` for complete security checklist.

---

## 🤝 Contributing

We welcome contributions! Here's how you can help:

### How to Contribute

1. **Fork the repository**
2. **Create a feature branch**
   ```bash
   git checkout -b feature/AmazingFeature
   ```
3. **Commit your changes**
   ```bash
   git commit -m 'Add some AmazingFeature'
   ```
4. **Push to the branch**
   ```bash
   git push origin feature/AmazingFeature
   ```
5. **Open a Pull Request**

### Contribution Guidelines

- Follow existing code style and conventions
- Write clear commit messages
- Add comments for complex logic
- Test thoroughly before submitting
- Update documentation if needed

### Areas for Contribution

- 🐛 Bug fixes
- ✨ New features
- 📝 Documentation improvements
- 🎨 UI/UX enhancements
- 🔒 Security improvements
- ♿ Accessibility features
- 🌐 Internationalization (i18n)

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

```
MIT License

Copyright (c) 2025 CharityBridge

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.
```

---

## 📞 Contact

### Project Maintainer
- **Name**: Your Name
- **Email**: your.email@example.com
- **GitHub**: [@yourusername](https://github.com/yourusername)

### Support
- 🐛 **Bug Reports**: [Open an issue](https://github.com/yourusername/CharityBridge2/issues)
- 💡 **Feature Requests**: [Open an issue](https://github.com/yourusername/CharityBridge2/issues)
- 💬 **Questions**: [Discussions](https://github.com/yourusername/CharityBridge2/discussions)

---

## 🙏 Acknowledgments

- **Font Awesome** for icons
- **Tailwind CSS** for styling framework
- **Razorpay** for payment integration
- **All contributors** who help improve CharityBridge

---

## 📈 Project Status

- ✅ Core functionality implemented
- ✅ Multi-role system complete
- ✅ Payment integration functional
- ✅ File upload system working
- ✅ Responsive design implemented
- ⚠️ Security hardening needed (see PRODUCTION_READINESS.md)
- 🔄 Active development

---

## 🗺️ Roadmap

### Version 2.0 (Planned)
- [ ] Email notifications
- [ ] Advanced analytics dashboard
- [ ] Mobile app (React Native)
- [ ] Multi-language support
- [ ] API for third-party integrations
- [ ] AI-powered NGO matching
- [ ] Blockchain for donation transparency
- [ ] Social media integration

---

<div align="center">

### ⭐ Star this repository if you find it helpful!

**Made with ❤️ by the CharityBridge Team**

[Back to Top](#-charitybridge)

</div>
