# Crowdfunding Platform

A comprehensive crowdfunding platform built with PHP, MySQL, and Bootstrap that enables users to create, manage, and donate to fundraising campaigns.

## 🚀 Features

### For Donors
- Browse and search campaigns by category
- View campaign details with real-time progress tracking
- Make donations with demo payment integration
- Leave messages with donations
- Anonymous donation option
- Share campaigns on social media (Facebook, Twitter, LinkedIn, Email)
- View donation history

### For Organizers
- Create and manage fundraising campaigns
- Upload campaign images
- Track donations in real-time
- View detailed donor information (for campaign owners)
- Edit campaign information
- Monitor funding progress with visual indicators
- Contact donors

### For Administrators
- Approve/reject campaign submissions
- Manage user accounts
- Monitor platform activity
- Category management
- User verification and status management

### General Features
- 🔐 Secure authentication system
- 🌓 Dark mode support
- 📱 Fully responsive design
- 📊 Real-time progress tracking with progress bars
- 💬 Donor messages and comments
- 📧 Email notifications
- 🔗 Social media sharing integration
- 📈 Campaign statistics dashboard

## 🛠 Tech Stack

- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5, Font Awesome 6
- **Backend**: PHP 7.4+, MySQL 5.7+, PDO for database operations
- **Authentication**: PHP Sessions
- **Development**: XAMPP/WAMP/LAMP stack

## 📋 Prerequisites

- PHP >= 7.4
- MySQL >= 5.7
- Web server (Apache/Nginx)
- Web browser (Chrome, Firefox, Safari, Edge)

## ⚡ Quick Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/crowdfunding-platform.git
   cd crowdfunding-platform
   ```

2. **Move to web server directory**
   
   For XAMPP:
   ```bash
   mv crowdfunding-platform /Applications/XAMPP/htdocs/
   ```
   
   For WAMP:
   ```bash
   mv crowdfunding-platform C:\wamp64\www\
   ```

3. **Set up the database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `crowdfunding_db`
   - Import the `database.sql` file from the project root

4. **Configure database connection**
   
   Edit `config/database.php`:
   ```php
   <?php
   $host = 'localhost';
   $dbname = 'crowdfunding_db';
   $username = 'root';
   $password = ''; // Change if you have MySQL password
   
   try {
       $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
       $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
   } catch(PDOException $e) {
       die("Connection failed: " . $e->getMessage());
   }
   ?>
   ```

5. **Create uploads directory**
   ```bash
   mkdir uploads
   mkdir uploads/campaigns
   chmod 755 uploads/
   chmod 755 uploads/campaigns/
   ```

6. **Access the application**
   - Open browser and navigate to `http://localhost/crowdfunding-platform`

## 📁 Project Structure

```
crowdfunding-platform/
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── main.js
│   └── images/
├── config/
│   └── database.php
├── includes/
│   ├── header.php
│   └── footer.php
├── uploads/
│   └── campaigns/
├── index.php              # Homepage with featured campaigns
├── campaign.php           # Campaign details page
├── create_campaign.php    # Create new campaign
├── edit_campaign.php      # Edit existing campaign
├── my_campaigns.php       # User's campaigns
├── dashboard.php          # User dashboard
├── login.php              # User login
├── register.php           # User registration
├── profile.php            # User profile
├── donate.php             # Make donation
├── demo_donate.php        # Demo donation
├── categories.php         # Browse by categories
└── admin/                 # Admin panel
    ├── index.php
    ├── manage_campaigns.php
    ├── manage_users.php
    └── manage_categories.php
```

## 🗄️ Database Schema

### Users Table
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('donor', 'organizer', 'both', 'admin') DEFAULT 'donor',
    status ENUM('pending', 'active', 'suspended') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Categories Table
```sql
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Campaigns Table
```sql
CREATE TABLE campaigns (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category_id INT,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    target_amount DECIMAL(10,2) NOT NULL,
    image_path VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);
```

### Donations Table
```sql
CREATE TABLE donations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    user_id INT,
    donor_name VARCHAR(100),
    amount DECIMAL(10,2) NOT NULL,
    message TEXT,
    is_anonymous BOOLEAN DEFAULT FALSE,
    payment_method VARCHAR(50),
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

## 👥 User Roles & Permissions

### 1. **Donor**
- View all approved campaigns
- Make donations
- Leave messages with donations
- View donation history
- Share campaigns
- Browse by categories

### 2. **Organizer**
- All donor permissions
- Create new campaigns (requires admin approval)
- Edit own campaigns
- View full donor details for own campaigns
- Track campaign progress
- Receive donation notifications

### 3. **Admin**
- Full platform access
- Approve/reject campaigns
- Manage users (activate/suspend)
- Manage categories
- View all campaigns and donations
- Access admin dashboard with statistics

## 📱 Responsive Design

The platform is fully responsive and works seamlessly on:
- 💻 Desktop (1920x1080 and above)
- 💻 Laptop (1366x768)
- 📱 Tablet (768x1024)
- 📱 Mobile (375x667)

## 🎯 Key Pages

### Homepage (index.php)
- Featured campaigns
- Category navigation
- Campaign search
- Statistics counter
- Call-to-action buttons

### Campaign Details (campaign.php)
- Campaign image
- Funding progress bar
- Organizer information
- Campaign description
- Recent donors list
- Share buttons
- Donation button

### User Dashboard (dashboard.php)
- My campaigns overview
- Donation history
- Profile management
- Statistics

## 🔐 Security Features

- Password hashing using `password_hash()`
- SQL injection prevention using PDO prepared statements
- XSS protection with `htmlspecialchars()`
- Session-based authentication
- CSRF protection on forms
- Input validation and sanitization
- Secure file upload validation

## 🌟 Demo Accounts

### Admin Account
- Username: `admin`
- Password: `admin123`

### Organizer Account
- Username: `organizer`
- Password: `organizer123`

### Donor Account
- Username: `donor`
- Password: `donor123`

## 📸 Screenshots

[Add screenshots of your application here]
- Homepage
- Campaign listing
- Campaign details
- Donation page
- User dashboard
- Admin panel

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 To-Do List

- [ ] Add email verification
- [ ] Implement real payment gateway
- [ ] Add campaign comments section
- [ ] Create mobile app
- [ ] Add multi-language support
- [ ] Implement campaign updates feature
- [ ] Add reward tiers for donors
- [ ] Create advanced search filters

## 🐛 Known Issues

- Image upload size limited to 5MB
- Demo payment only (not real transactions)
- No email verification yet

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Bootstrap team for the amazing framework
- Font Awesome for beautiful icons
- All contributors and testers
- Open source community

## 📞 Contact & Support

- **Developer**: Your Name
- **Email**: your.email@example.com
- **GitHub**: [@yourusername](https://github.com/yourusername)
- **Project Link**: [https://github.com/yourusername/crowdfunding-platform](https://github.com/yourusername/crowdfunding-platform)

---

**⭐ Star this repository if you find it useful!**

**Happy Fundraising!** 🚀
