# Calvin Christian Portfolio
## Setup Guide for XAMPP on macOS

---

### 1. Install & Start XAMPP
1. Download XAMPP from https://www.apachefriends.org/
2. Open XAMPP Control Panel
3. Start **Apache** and **MySQL**

---

### 2. Copy Project Files
Copy the entire `calvin_portfolio` folder to XAMPP's web root:

```
/Applications/XAMPP/xamppfiles/htdocs/calvin_portfolio/
```

You can do this in Terminal:
```bash
cp -r ~/Downloads/calvin_portfolio /Applications/XAMPP/xamppfiles/htdocs/
```

---

### 3. Import the Database
1. Open your browser and go to: http://localhost/phpmyadmin
2. Click **"New"** in the left sidebar → name it `calvin_portfolio` → click **Create**
3. Click your new database → click **Import** tab
4. Click **Choose File** → select `calvin_portfolio/database.sql`
5. Click **Go** (Import)

---

### 4. Configure Database (if needed)
If your XAMPP MySQL has a different username/password, edit:
```
php/config.php
```
Change `DB_USER` and `DB_PASS` to match your setup.
Default XAMPP uses: user = `root`, password = (empty)

---

### 5. Upload Folder Permissions
Make sure the uploads folder is writable. In Terminal:
```bash
chmod -R 755 /Applications/XAMPP/xamppfiles/htdocs/calvin_portfolio/uploads/
```

---

### 6. Visit Your Portfolio
- **Portfolio site:** http://localhost/calvin_portfolio/
- **Admin panel:**   http://localhost/calvin_portfolio/admin/

---

### 7. Add Your Media

#### Via Admin Panel (recommended)
1. Go to http://localhost/calvin_portfolio/admin/
2. Click **"Upload Media"**
3. Fill in title, category, select type (photo/video)
4. Choose your file and click **Upload**
5. Uploaded files go into `uploads/photos/` or `uploads/videos/`

#### Manually (for bulk)
Copy files directly into:
- Photos → `uploads/photos/`
- Videos → `uploads/videos/`

Then insert records in phpMyAdmin:
```sql
INSERT INTO media (title, type, category, file_path, featured) 
VALUES ('My Photo', 'photo', 'landscape', 'uploads/photos/myfile.jpg', 1);
```

---

### 8. Add Your Hero Video
Place a video named `hero.mp4` in:
```
uploads/videos/hero.mp4
```
If no hero video is found, a gradient fallback is shown automatically.

---

### 9. Add Your Profile Photo
Place your photo at:
```
uploads/photos/calvin.jpg
```

---

### 10. Customize Social Links
Open `index.html` and find the Contact section.
Update the Instagram, YouTube, and Vimeo links:
```html
<a href="https://instagram.com/YOUR_HANDLE" class="social-link">Instagram</a>
```

---

### File Structure
```
calvin_portfolio/
├── index.html          ← Main portfolio page
├── css/
│   └── style.css       ← All styles
├── js/
│   └── main.js         ← Carousel, parallax, gallery, API calls
├── php/
│   ├── config.php      ← DB config (edit this)
│   ├── api_media.php   ← Fetch photos/videos
│   ├── api_projects.php← Fetch projects
│   ├── api_contact.php ← Handle contact form
│   ├── api_upload.php  ← Handle file uploads
│   ├── api_messages.php← Admin: view messages
│   ├── api_save_project.php ← Admin: save project
│   └── api_delete.php  ← Admin: delete items
├── admin/
│   └── index.php       ← Admin dashboard
├── uploads/
│   ├── photos/         ← Your photos go here
│   └── videos/         ← Your videos go here
└── database.sql        ← Import this first!
```

---

### Notes
- The portfolio uses **parallax scroll** effects as you scroll down
- The **carousel** auto-advances every 5 seconds (pauses on hover)
- The **gallery** supports filtering by photo/video
- Videos in the gallery **preview on hover**
- Click any item to open it in the **lightbox**
- The **contact form** saves messages to the database (check Admin → Messages)
- The **admin panel** is accessible via the ⚙ icon (bottom-right corner)
