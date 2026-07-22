// ===== DOM READY =====
document.addEventListener('DOMContentLoaded', function () {

  // ===== MOBILE NAV TOGGLE =====
  const navToggle = document.getElementById('navToggle');
  const navLinks = document.getElementById('navLinks');

  if (navToggle && navLinks) {
    navToggle.addEventListener('click', function () {
      this.classList.toggle('active');
      navLinks.classList.toggle('open');
    });

    document.addEventListener('click', function (e) {
      if (!navToggle.contains(e.target) && !navLinks.contains(e.target)) {
        navToggle.classList.remove('active');
        navLinks.classList.remove('open');
      }
    });
  }

  // ===== STICKY NAVBAR SCROLL EFFECT =====
  const navbar = document.getElementById('navbar');
  if (navbar) {
    window.addEventListener('scroll', function () {
      if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    });
  }

  // ===== DARK MODE TOGGLE =====
  const themeToggle = document.getElementById('themeToggle');
  if (themeToggle) {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
      document.documentElement.setAttribute('data-theme', 'dark');
      themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
    }

    themeToggle.addEventListener('click', function () {
      const currentTheme = document.documentElement.getAttribute('data-theme');
      if (currentTheme === 'dark') {
        document.documentElement.removeAttribute('data-theme');
        localStorage.setItem('theme', 'light');
        this.innerHTML = '<i class="fas fa-moon"></i>';
      } else {
        document.documentElement.setAttribute('data-theme', 'dark');
        localStorage.setItem('theme', 'dark');
        this.innerHTML = '<i class="fas fa-sun"></i>';
      }
    });
  }

  // ===== PASSWORD TOGGLE =====
  document.querySelectorAll('.toggle-password').forEach(function (toggle) {
    toggle.addEventListener('click', function () {
      const input = this.parentElement.querySelector('input');
      if (!input) return;
      if (input.type === 'password') {
        input.type = 'text';
        this.classList.remove('fa-eye');
        this.classList.add('fa-eye-slash');
      } else {
        input.type = 'password';
        this.classList.remove('fa-eye-slash');
        this.classList.add('fa-eye');
      }
    });
  });

  // ===== SMOOTH SCROLL =====
  document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
    anchor.addEventListener('click', function (e) {
      const targetId = this.getAttribute('href');
      if (targetId === '#') return;
      const target = document.querySelector(targetId);
      if (target) {
        e.preventDefault();
        const offset = navbar ? navbar.offsetHeight : 70;
        const targetPos = target.getBoundingClientRect().top + window.pageYOffset - offset;
        window.scrollTo({ top: targetPos, behavior: 'smooth' });
      }
    });
  });

  // ===== SCROLL ANIMATIONS =====
  const animateElements = document.querySelectorAll('.animate-on-scroll');
  if (animateElements.length > 0) {
    const observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
        }
      });
    }, { threshold: 0.1 });

    animateElements.forEach(function (el) {
      observer.observe(el);
    });
  }

  // ===== TOAST NOTIFICATION SYSTEM =====
  window.showToast = function (type, title, message) {
    let container = document.querySelector('.toast-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'toast-container';
      document.body.appendChild(container);
    }

    const icons = {
      success: 'fas fa-check-circle',
      error: 'fas fa-times-circle',
      warning: 'fas fa-exclamation-triangle',
      info: 'fas fa-info-circle'
    };

    const toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.innerHTML =
      '<i class="' + (icons[type] || icons.success) + '"></i>' +
      '<div class="toast-text">' +
      '<h4>' + title + '</h4>' +
      '<p>' + message + '</p>' +
      '</div>' +
      '<button class="toast-close">&times;</button>';

    container.appendChild(toast);

    toast.querySelector('.toast-close').addEventListener('click', function () {
      toast.remove();
    });

    setTimeout(function () {
      if (toast.parentNode) {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(function () { toast.remove(); }, 300);
      }
    }, 5000);
  };

  const xamppAppBase = 'http://localhost/umma-voting/';

  function shouldUseXamppBase() {
    return window.location.protocol === 'file:' ||
      window.location.pathname.toLowerCase().indexOf('/umma-voting/') === -1;
  }

  function appUrl(path) {
    return shouldUseXamppBase() ? xamppAppBase + path : path;
  }

  function apiUrl(url) {
    if (/^https?:\/\//i.test(url) || url.indexOf('api/') !== 0) {
      return url;
    }

    return appUrl(url);
  }

  async function requestJson(url, options) {
    let response;
    const resolvedUrl = apiUrl(url);
    try {
      response = await fetch(resolvedUrl, options || {});
    } catch (error) {
      throw new Error('The service is temporarily unavailable. Please try again later.');
    }

    const responseText = await response.text();
    let payload = {};
    try {
      payload = responseText ? JSON.parse(responseText) : {};
    } catch (e) {
      payload = {};
    }

    if (!response.ok) {
      var fallbackMessage = 'Request failed (' + response.status + ')';
      if (response.status === 404) {
        fallbackMessage = 'API file not found. Open the site from http://localhost/umma-voting.';
      } else if (response.status >= 500) {
        fallbackMessage = 'Server error. Check Apache/PHP and the database.';
      }

      throw new Error(payload.error || payload.message || fallbackMessage);
    }

    if (!responseText || !Object.keys(payload).length) {
      throw new Error('The service returned an invalid response. Please try again later.');
    }

    return payload;
  }

  function setButtonLoading(button, isLoading, loadingText) {
    if (!button) return;
    if (isLoading) {
      button.dataset.originalHtml = button.innerHTML;
      button.disabled = true;
      button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + loadingText;
    } else {
      button.disabled = false;
      if (button.dataset.originalHtml) {
        button.innerHTML = button.dataset.originalHtml;
        delete button.dataset.originalHtml;
      }
    }
  }

  function escapeHtml(value) {
    return String(value === null || value === undefined ? '' : value).replace(/[&<>"']/g, function (char) {
      return {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      }[char];
    });
  }

  // ===== MODAL SYSTEM =====
  window.openModal = function (modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.add('show');
      document.body.style.overflow = 'hidden';
    }
  };

  window.closeModal = function (modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.remove('show');
      document.body.style.overflow = '';
    }
  };

  document.querySelectorAll('.modal-overlay').forEach(function (modal) {
    modal.addEventListener('click', function (e) {
      if (e.target === this) {
        this.classList.remove('show');
        document.body.style.overflow = '';
      }
    });
  });

  // ===== LOGIN FORM VALIDATION =====
  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      let valid = true;

      const username = document.getElementById('username');
      const password = document.getElementById('password');
      const usernameError = document.getElementById('usernameError');
      const passwordError = document.getElementById('passwordError');
      const submitButton = loginForm.querySelector('button[type="submit"]');

      if (!username.value.trim()) {
        username.classList.add('error');
        usernameError.classList.add('show');
        valid = false;
      } else {
        username.classList.remove('error');
        usernameError.classList.remove('show');
      }

      if (!password.value.trim()) {
        password.classList.add('error');
        passwordError.classList.add('show');
        valid = false;
      } else {
        password.classList.remove('error');
        passwordError.classList.remove('show');
      }

      if (valid) {
        var name = username.value.trim();
        var isAdminLogin = /^(admin|admin@gmail\.com)$/i.test(name);

        setButtonLoading(submitButton, true, 'Logging in...');
        try {
          var formData = new FormData(loginForm);
          formData.set('username', name);
          formData.set('password', password.value);

          var result = await requestJson(isAdminLogin ? 'api/admin/login.php' : 'api/login.php', {
            method: 'POST',
            body: formData
          });

          if (isAdminLogin) {
            localStorage.setItem('adminToken', result.token);
            localStorage.setItem('adminUser', JSON.stringify(result.admin));
            localStorage.removeItem('userToken');
            localStorage.removeItem('currentUser');
            showToast('success', 'Admin Login', 'Welcome back, administrator.');
            setTimeout(function () {
              window.location.href = appUrl('admin.html');
            }, 700);
          } else {
            localStorage.setItem('userToken', result.token);
            localStorage.setItem('currentUser', JSON.stringify(result.user));
            localStorage.setItem('voterName', result.user.full_name || result.user.username);
            localStorage.removeItem('adminToken');
            localStorage.removeItem('adminUser');
            showToast('success', 'Login Successful', 'Redirecting to your dashboard.');
            setTimeout(function () {
              window.location.href = appUrl('dashboard.html');
            }, 700);
          }
        } catch (error) {
          showToast('error', 'Login Failed', error.message || 'The login service is unavailable.');
        } finally {
          setButtonLoading(submitButton, false);
        }
      }
    });

    document.querySelectorAll('#loginForm input').forEach(function (input) {
      input.addEventListener('input', function () {
        this.classList.remove('error');
        const errorEl = document.getElementById(this.id + 'Error');
        if (errorEl) errorEl.classList.remove('show');
      });
    });
  }

  // ===== SIGNUP FORM VALIDATION =====
  const signupForm = document.getElementById('signupForm');
  if (signupForm) {
    signupForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      let valid = true;
      const submitButton = signupForm.querySelector('button[type="submit"]');

      const fields = [
        { id: 'fullname', errorId: 'fullnameError', validate: function (v) { return v.trim().length >= 3; } },
        { id: 'nationalId', errorId: 'nationalIdError', validate: function (v) { return v.trim().length >= 5; } },
        { id: 'phone', errorId: 'phoneError', validate: function (v) { return v.trim().length >= 8; } },
        { id: 'email', errorId: 'emailError', validate: function (v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()) && !/^admin@gmail\.com$/i.test(v.trim()); } },
        { id: 'regUsername', errorId: 'regUsernameError', validate: function (v) { return v.trim().length >= 4 && !/^(admin|administrator)$/i.test(v.trim()); } },
        { id: 'regPassword', errorId: 'regPasswordError', validate: function (v) { return v.trim().length >= 6; } }
      ];

      fields.forEach(function (field) {
        const input = document.getElementById(field.id);
        const error = document.getElementById(field.errorId);
        if (!field.validate(input.value)) {
          input.classList.add('error');
          if (error) error.classList.add('show');
          valid = false;
        } else {
          input.classList.remove('error');
          if (error) error.classList.remove('show');
        }
      });

      const confirmPassword = document.getElementById('confirmPassword');
      const regPassword = document.getElementById('regPassword');
      const confirmError = document.getElementById('confirmPasswordError');
      if (confirmPassword && regPassword) {
        if (confirmPassword.value !== regPassword.value || !confirmPassword.value.trim()) {
          confirmPassword.classList.add('error');
          if (confirmError) confirmError.classList.add('show');
          valid = false;
        } else {
          confirmPassword.classList.remove('error');
          if (confirmError) confirmError.classList.remove('show');
        }
      }

      if (valid) {
        setButtonLoading(submitButton, true, 'Creating account...');
        try {
          var result = await requestJson('api/register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              fullname: document.getElementById('fullname').value.trim(),
              nationalId: document.getElementById('nationalId').value.trim(),
              phone: document.getElementById('phone').value.trim(),
              email: document.getElementById('email').value.trim(),
              username: document.getElementById('regUsername').value.trim(),
              password: document.getElementById('regPassword').value
            })
          });

          localStorage.setItem('userToken', result.token);
          localStorage.setItem('currentUser', JSON.stringify(result.user));
          localStorage.setItem('voterName', result.user.full_name || result.user.username);
          localStorage.removeItem('adminToken');
          localStorage.removeItem('adminUser');
          showToast('success', 'Registration Successful', 'Your account has been saved. Redirecting to your dashboard.');
          setTimeout(function () {
            window.location.href = appUrl('dashboard.html');
          }, 900);
        } catch (error) {
          showToast('error', 'Registration Failed', error.message);
        } finally {
          setButtonLoading(submitButton, false);
        }
      }
    });

    document.querySelectorAll('#signupForm input').forEach(function (input) {
      input.addEventListener('input', function () {
        this.classList.remove('error');
        const errorEl = document.getElementById(this.id + 'Error');
        if (errorEl) errorEl.classList.remove('show');
        if (this.id === 'regPassword' || this.id === 'confirmPassword') {
          const confirm = document.getElementById('confirmPassword');
          const confirmErr = document.getElementById('confirmPasswordError');
          if (confirm && confirm.value) {
            if (confirm.value !== document.getElementById('regPassword').value) {
              confirm.classList.add('error');
              if (confirmErr) confirmErr.classList.add('show');
            } else {
              confirm.classList.remove('error');
              if (confirmErr) confirmErr.classList.remove('show');
            }
          }
        }
      });
    });
  }

  // ===== CONTACT FORM VALIDATION =====
  const contactForm = document.getElementById('contactForm');
  if (contactForm) {
    contactForm.addEventListener('submit', function (e) {
      e.preventDefault();
      let valid = true;

      const fields = [
        { id: 'contactName', errorId: 'contactNameError', validate: function (v) { return v.trim().length >= 2; } },
        { id: 'contactEmail', errorId: 'contactEmailError', validate: function (v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()); } },
        { id: 'contactSubject', errorId: 'contactSubjectError', validate: function (v) { return v.trim().length >= 3; } },
        { id: 'contactMessage', errorId: 'contactMessageError', validate: function (v) { return v.trim().length >= 10; } }
      ];

      fields.forEach(function (field) {
        const input = document.getElementById(field.id);
        const error = document.getElementById(field.errorId);
        if (!field.validate(input ? input.value : '')) {
          if (input) input.classList.add('error');
          if (error) error.classList.add('show');
          valid = false;
        } else {
          if (input) input.classList.remove('error');
          if (error) error.classList.remove('show');
        }
      });

      if (valid) {
        showToast('success', 'Message Sent', 'Thank you for reaching out. We will get back to you shortly.');
        contactForm.reset();
      }
    });

    document.querySelectorAll('#contactForm input, #contactForm textarea').forEach(function (input) {
      input.addEventListener('input', function () {
        this.classList.remove('error');
        const errorEl = document.getElementById(this.id + 'Error');
        if (errorEl) errorEl.classList.remove('show');
      });
    });
  }

  // ===== VOTING SYSTEM =====
  window.selectedCandidate = null;

  window.confirmVote = function (btn, candidateName) {
    window.selectedCandidate = { btn: btn, name: candidateName };
    document.getElementById('candidateName').textContent = candidateName;
    openModal('voteModal');
  };

  window.submitVote = function () {
    closeModal('voteModal');
    if (window.selectedCandidate) {
      var btn = window.selectedCandidate.btn;
      var card = btn.closest('.candidate-card');
      if (card) {
        card.classList.add('voted');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-check-circle"></i> Voted';
        btn.style.background = '#198754';
      }

      document.querySelectorAll('.candidate-card').forEach(function (c) {
        if (c !== card) {
          c.querySelector('.btn-vote').disabled = true;
          c.style.opacity = '0.5';
        }
      });

      setTimeout(function () {
        openModal('successModal');
      }, 500);
    }
  };

  // ===== DISPLAY USER NAME ON DASHBOARD & PROFILE =====
  var displayName = document.getElementById('displayName');
  if (displayName) {
    var storedName = localStorage.getItem('voterName');
    if (storedName) {
      displayName.textContent = storedName;
    }
  }

  var profileName = document.getElementById('profileName');
  if (profileName) {
    var storedName = localStorage.getItem('voterName');
    if (storedName) {
      profileName.textContent = storedName;
    }
  }

  // ===== NOTIFICATION BUTTON =====
  var notifBtn = document.getElementById('notifBtn');
  if (notifBtn) {
    notifBtn.addEventListener('click', function () {
      showToast('info', 'Notifications', 'You have 3 unread notifications.');
    });
  }

  // ===== ADMIN SIDEBAR TOGGLE =====
  var sidebarToggle = document.getElementById('sidebarToggle');
  var adminSidebar = document.getElementById('adminSidebar');
  if (sidebarToggle && adminSidebar) {
    sidebarToggle.addEventListener('click', function () {
      adminSidebar.classList.toggle('open');
    });
    document.addEventListener('click', function (e) {
      if (window.innerWidth <= 1024) {
        if (!adminSidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
          adminSidebar.classList.remove('open');
        }
      }
    });
    window.addEventListener('resize', function () {
      if (window.innerWidth > 1024) {
        adminSidebar.classList.remove('open');
      }
    });
  }

  // ===== ACTIVE NAV LINK =====
  var currentPage = window.location.pathname.split('/').pop();
  document.querySelectorAll('.nav-links a, .sidebar-nav a').forEach(function (link) {
    var href = link.getAttribute('href');
    if (href === currentPage) {
      link.classList.add('active');
    }
  });

  // ===== ADMIN VOTERS FROM DATABASE =====
  async function loadAdminVoters(page) {
    if (currentPage !== 'admin-voters.html') return;

    var tableBody = document.querySelector('.admin-card table tbody');
    var statValues = document.querySelectorAll('.admin-stat-card .stat-info h3');
    var statLabels = document.querySelectorAll('.admin-stat-card .stat-info p');
    var paginationInfo = document.querySelector('.pagination-info');
    var searchInput = document.getElementById('voterSearchInput');
    var statusFilter = document.getElementById('voterStatusFilter');
    var adminToken = localStorage.getItem('adminToken');

    if (!tableBody) return;

    if (!adminToken) {
      tableBody.innerHTML = '<tr><td colspan="7">Please login as admin first using admin@gmail.com and 123456.</td></tr>';
      if (paginationInfo) paginationInfo.textContent = 'Admin login required';
      return;
    }

    tableBody.innerHTML = '<tr><td colspan="7">Loading voters...</td></tr>';

    try {
      var params = new URLSearchParams();
      params.set('page', page || 1);
      if (searchInput && searchInput.value.trim()) params.set('search', searchInput.value.trim());
      if (statusFilter && statusFilter.value) params.set('status', statusFilter.value);

      var result = await requestJson('api/admin/voters.php?' + params.toString(), {
        headers: { 'Authorization': 'Bearer ' + adminToken }
      });

      var stats = result.stats || {};
      if (statValues.length >= 4) {
        statValues[0].textContent = stats.total || 0;
        statValues[1].textContent = stats.voted || 0;
        statValues[2].textContent = stats.not_voted || 0;
        statValues[3].textContent = stats.inactive || 0;
      }
      if (statLabels.length >= 4) {
        statLabels[0].textContent = 'Registered Voters';
        statLabels[1].textContent = 'Votes Cast';
        statLabels[2].textContent = 'Pending Votes';
        statLabels[3].textContent = 'Inactive Accounts';
      }

      if (!result.voters || result.voters.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="7">No registered voters found.</td></tr>';
      } else {
        tableBody.innerHTML = result.voters.map(function (voter, index) {
          var rowNumber = ((result.page || 1) - 1) * 20 + index + 1;
          var voted = Number(voter.voted) === 1;
          var badgeClass = voter.status === 'inactive' ? 'badge-danger' : (voted ? 'badge-success' : 'badge-warning');
          var badgeText = voter.status === 'inactive' ? 'Inactive' : (voted ? 'Voted' : 'Not Voted');
          var votedOn = voter.last_vote_at ? new Date(voter.last_vote_at.replace(' ', 'T')).toLocaleString() : '-';

          return '<tr>' +
            '<td>' + rowNumber + '</td>' +
            '<td><strong>' + escapeHtml(voter.full_name) + '</strong></td>' +
            '<td>' + escapeHtml(voter.student_id) + '</td>' +
            '<td>' + escapeHtml(voter.email) + '</td>' +
            '<td>' + escapeHtml(voter.phone) + '</td>' +
            '<td><span class="badge ' + badgeClass + '">' + badgeText + '</span></td>' +
            '<td>' + escapeHtml(votedOn) + '</td>' +
            '</tr>';
        }).join('');
      }

      if (paginationInfo) {
        var start = result.total ? (((result.page || 1) - 1) * 20 + 1) : 0;
        var end = Math.min((result.page || 1) * 20, result.total || 0);
        paginationInfo.textContent = 'Showing ' + start + '-' + end + ' of ' + (result.total || 0) + ' voters';
      }
    } catch (error) {
      tableBody.innerHTML = '<tr><td colspan="7">' + escapeHtml(error.message) + '</td></tr>';
      if (paginationInfo) paginationInfo.textContent = 'Could not load voters';
    }
  }

  if (currentPage === 'admin-voters.html') {
    var voterSearchBtn = document.getElementById('voterSearchBtn');
    var voterSearchInput = document.getElementById('voterSearchInput');
    var voterStatusFilter = document.getElementById('voterStatusFilter');

    if (voterSearchBtn) voterSearchBtn.addEventListener('click', function () { loadAdminVoters(1); });
    if (voterStatusFilter) voterStatusFilter.addEventListener('change', function () { loadAdminVoters(1); });
    if (voterSearchInput) {
      voterSearchInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') loadAdminVoters(1);
      });
    }

    loadAdminVoters(1);
  }

  // ===== STUDENT DASHBOARD FROM DATABASE =====
  function formatDateTime(value) {
    if (!value) return '-';
    var date = new Date(String(value).replace(' ', 'T'));
    if (isNaN(date.getTime())) return value;
    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
  }

  async function loadStudentDashboard() {
    if (currentPage !== 'dashboard.html') return;

    var cards = document.querySelectorAll('.dash-card');
    var statusTitle = document.querySelector('[data-key="statusTitle"]');
    var statusSub = document.querySelector('[data-key="statusSub"]');
    var progressFill = document.querySelector('.progress-bar-fill');
    var progressVal = document.querySelector('[data-key="progressVal"]');
    var notificationBadge = document.querySelector('.notification-badge');
    var voteButtons = document.querySelectorAll('a[href="voting.html"]');

    try {
      var electionsResult = await requestJson('api/elections.php?status=active');
      var candidatesResult = await requestJson('api/candidates.php?status=active');
      var elections = electionsResult.elections || [];
      var candidates = candidatesResult.candidates || [];
      var activeElection = elections[0] || null;
      var turnout = activeElection ? Number(activeElection.turnout || 0) : 0;
      var daysRemaining = '-';

      if (activeElection && activeElection.end_date) {
        var endDate = new Date(String(activeElection.end_date).replace(' ', 'T'));
        var diff = endDate.getTime() - Date.now();
        daysRemaining = Math.max(0, Math.ceil(diff / 86400000));
      }

      if (cards.length >= 4) {
        cards[0].querySelector('.card-value').textContent = elections.length;
        cards[0].querySelector('.card-sub').textContent = activeElection ? activeElection.title : 'No active election';
        cards[1].querySelector('.card-value').textContent = candidates.length;
        cards[1].querySelector('.card-sub').textContent = candidates.length === 1 ? 'Active candidate' : 'Active candidates';
        cards[2].querySelector('.card-value').textContent = turnout + '%';
        cards[2].querySelector('.card-sub').textContent = 'Based on registered voters';
        cards[3].querySelector('.card-value').textContent = daysRemaining;
        cards[3].querySelector('.card-sub').textContent = activeElection ? 'Until polls close' : 'No deadline set';
      }

      if (statusTitle) statusTitle.textContent = activeElection ? 'Voting In Progress' : 'No Active Election';
      if (statusSub) statusSub.textContent = activeElection ? activeElection.title : 'Please check back after an election is created.';
      if (progressFill) progressFill.style.width = Math.min(100, Math.max(0, turnout)) + '%';
      if (progressVal) progressVal.textContent = turnout + '%';

      if (notificationBadge) notificationBadge.textContent = activeElection ? '1' : '0';
      voteButtons.forEach(function (button) {
        if (!activeElection) {
          button.classList.add('disabled');
          button.setAttribute('aria-disabled', 'true');
        }
      });
    } catch (error) {
      showToast('error', 'Dashboard Error', error.message);
    }
  }

  // ===== ADMIN DASHBOARD FROM DATABASE =====
  async function loadAdminDashboard() {
    if (currentPage !== 'admin.html' && currentPage !== 'dashbord.html') return;

    var adminToken = localStorage.getItem('adminToken');
    var statValues = document.querySelectorAll('.admin-stat-card .stat-info h3');
    var statLabels = document.querySelectorAll('.admin-stat-card .stat-info p');
    var tables = document.querySelectorAll('.admin-card table tbody');
    var activityList = document.querySelector('.admin-activity');
    var chartPlaceholder = document.querySelector('.chart-placeholder');

    if (!adminToken) {
      if (tables[0]) tables[0].innerHTML = '<tr><td colspan="6">Please login as admin first using admin@gmail.com and 123456.</td></tr>';
      if (tables[1]) tables[1].innerHTML = '<tr><td colspan="5">Please login as admin first using admin@gmail.com and 123456.</td></tr>';
      if (activityList) {
        activityList.innerHTML =
          '<div class="activity-item">' +
          '<div class="activity-icon"><i class="fas fa-lock"></i></div>' +
          '<div class="activity-text"><p>Admin login required.</p><span>Use the login page first</span></div>' +
          '</div>';
      }
      return;
    }

    try {
      var dashboard = await requestJson('api/admin/dashboard.php', {
        headers: { 'Authorization': 'Bearer ' + adminToken }
      });
      var electionsResult = await requestJson('api/elections.php');
      var stats = dashboard.stats || {};
      var topCandidates = dashboard.top_candidates || [];
      var recentVotes = dashboard.recent_votes || [];
      var elections = electionsResult.elections || [];

      if (statValues.length >= 4) {
        statValues[0].textContent = stats.total_students || 0;
        statValues[1].textContent = stats.total_votes || 0;
        statValues[2].textContent = stats.total_candidates || 0;
        statValues[3].textContent = stats.pending_candidates || 0;
      }
      if (statLabels.length >= 4) {
        statLabels[0].textContent = 'Registered Students';
        statLabels[1].textContent = 'Votes Cast';
        statLabels[2].textContent = 'Total Candidates';
        statLabels[3].textContent = 'Pending Candidates';
      }

      if (tables[0]) {
        tables[0].innerHTML = topCandidates.length ? topCandidates.map(function (candidate, index) {
          var status = candidate.status || 'active';
          var badgeClass = status === 'pending' ? 'badge-warning' : (status === 'rejected' ? 'badge-danger' : 'badge-success');
          return '<tr>' +
            '<td>' + (index + 1) + '</td>' +
            '<td>' + escapeHtml(candidate.name) + '</td>' +
            '<td>' + escapeHtml(candidate.party) + '</td>' +
            '<td>' + escapeHtml(candidate.position || 'SRC President') + '</td>' +
            '<td>' + escapeHtml(candidate.votes || 0) + '</td>' +
            '<td><span class="badge ' + badgeClass + '">' + escapeHtml(status.charAt(0).toUpperCase() + status.slice(1)) + '</span></td>' +
            '</tr>';
        }).join('') : '<tr><td colspan="6">No active candidates yet.</td></tr>';
      }

      if (tables[1]) {
        tables[1].innerHTML = elections.length ? elections.slice(0, 5).map(function (election) {
          var status = election.status || 'upcoming';
          var badgeClass = status === 'active' ? 'badge-success' : (status === 'completed' ? 'badge-warning' : 'badge-danger');
          return '<tr>' +
            '<td>' + escapeHtml(election.title) + '</td>' +
            '<td>' + escapeHtml(formatDateTime(election.start_date)) + '</td>' +
            '<td>' + escapeHtml(formatDateTime(election.end_date)) + '</td>' +
            '<td>' + escapeHtml(election.total_voters || 0) + '</td>' +
            '<td><span class="badge ' + badgeClass + '">' + escapeHtml(status.charAt(0).toUpperCase() + status.slice(1)) + '</span></td>' +
            '</tr>';
        }).join('') : '<tr><td colspan="5">No elections have been created yet.</td></tr>';
      }

      if (chartPlaceholder) {
        chartPlaceholder.innerHTML =
          '<div style="text-align:center;">' +
          '<strong style="font-size:2rem;color:var(--primary);">' + escapeHtml(stats.turnout || 0) + '%</strong>' +
          '<p style="margin-top:8px;color:var(--gray-600);">Current turnout</p>' +
          '</div>';
      }

      if (activityList) {
        activityList.innerHTML = recentVotes.length ? recentVotes.map(function (vote) {
          return '<div class="activity-item">' +
            '<div class="activity-icon"><i class="fas fa-vote-yea"></i></div>' +
            '<div class="activity-text">' +
            '<p>Vote cast by <strong>' + escapeHtml(vote.voter) + '</strong> for ' + escapeHtml(vote.candidate) + '</p>' +
            '<span>' + escapeHtml(formatDateTime(vote.voted_at)) + '</span>' +
            '</div>' +
            '</div>';
        }).join('') :
          '<div class="activity-item">' +
          '<div class="activity-icon"><i class="fas fa-info-circle"></i></div>' +
          '<div class="activity-text"><p>No recent vote activity yet.</p><span>Activity appears after students vote</span></div>' +
          '</div>';
      }
    } catch (error) {
      showToast('error', 'Admin Dashboard Error', error.message);
      if (tables[0]) tables[0].innerHTML = '<tr><td colspan="6">' + escapeHtml(error.message) + '</td></tr>';
      if (tables[1]) tables[1].innerHTML = '<tr><td colspan="5">' + escapeHtml(error.message) + '</td></tr>';
    }
  }

  loadStudentDashboard();
  loadAdminDashboard();

  // ===== EDIT MODE SYSTEM =====
  var editModeEnabled = false;
  var editModeBtn = document.getElementById('editModeBtn');
  var editModeBanner = document.getElementById('editModeBanner');

  if (editModeBanner) {
    editModeBanner.style.display = 'none';
  }

  function loadSavedEdits() {
    var saved = localStorage.getItem('dashboardEdits');
    if (!saved) return;
    try {
      var edits = JSON.parse(saved);
      document.querySelectorAll('.editable[data-key]').forEach(function (el) {
        var key = el.getAttribute('data-key');
        if (edits[key] !== undefined) {
          el.textContent = edits[key];
        }
      });
    } catch (e) {}
  }

  function saveEdits() {
    var edits = {};
    document.querySelectorAll('.editable[data-key]').forEach(function (el) {
      edits[el.getAttribute('data-key')] = el.textContent;
    });
    localStorage.setItem('dashboardEdits', JSON.stringify(edits));
  }

  loadSavedEdits();

  if (editModeBtn) {
    editModeBtn.addEventListener('click', function () {
      editModeEnabled = !editModeEnabled;
      if (editModeEnabled) {
        document.body.classList.add('edit-mode-active');
        editModeBtn.classList.add('active');
        editModeBtn.innerHTML = '<i class="fas fa-check"></i>';
        if (editModeBanner) editModeBanner.style.display = 'flex';
        document.querySelectorAll('.editable').forEach(function (el) {
          el.setAttribute('contenteditable', 'true');
          el.setAttribute('tabindex', '0');
        });
        showToast('info', 'Edit Mode Enabled', 'Click any highlighted text to edit it. Click Save when done.');
      } else {
        document.body.classList.remove('edit-mode-active');
        editModeBtn.classList.remove('active');
        editModeBtn.innerHTML = '<i class="fas fa-pen"></i>';
        if (editModeBanner) editModeBanner.style.display = 'none';
        document.querySelectorAll('.editable').forEach(function (el) {
          el.removeAttribute('contenteditable');
          el.removeAttribute('tabindex');
        });
      }
    });
  }

  var saveEditsBtn = document.getElementById('saveEditsBtn');
  if (saveEditsBtn) {
    saveEditsBtn.addEventListener('click', function () {
      saveEdits();
      editModeEnabled = false;
      document.body.classList.remove('edit-mode-active');
      editModeBtn.classList.remove('active');
      editModeBtn.innerHTML = '<i class="fas fa-pen"></i>';
      if (editModeBanner) editModeBanner.style.display = 'none';
      document.querySelectorAll('.editable').forEach(function (el) {
        el.removeAttribute('contenteditable');
        el.removeAttribute('tabindex');
      });
      showToast('success', 'Saved', 'All dashboard changes have been saved successfully.');
    });
  }

  var cancelEditsBtn = document.getElementById('cancelEditsBtn');
  if (cancelEditsBtn) {
    cancelEditsBtn.addEventListener('click', function () {
      editModeEnabled = false;
      document.body.classList.remove('edit-mode-active');
      editModeBtn.classList.remove('active');
      editModeBtn.innerHTML = '<i class="fas fa-pen"></i>';
      if (editModeBanner) editModeBanner.style.display = 'none';
      document.querySelectorAll('.editable').forEach(function (el) {
        el.removeAttribute('contenteditable');
        el.removeAttribute('tabindex');
      });
      loadSavedEdits();
      showToast('info', 'Cancelled', 'Changes have been discarded. Previous values restored.');
    });
  }

  // ===== PROFILE EDIT MODE =====
  var profileEditToggle = document.getElementById('profileEditToggle');
  var saveProfileBtn = document.getElementById('saveProfileBtn');

  if (profileEditToggle) {
    profileEditToggle.addEventListener('click', function () {
      var isEditing = document.body.classList.toggle('edit-mode-active');
      if (isEditing) {
        profileEditToggle.innerHTML = '<i class="fas fa-times"></i> Cancel';
        profileEditToggle.style.background = 'rgba(220,53,69,0.3)';
        profileEditToggle.style.borderColor = 'var(--danger)';
        if (saveProfileBtn) saveProfileBtn.style.display = 'flex';
        document.querySelectorAll('.profile-page .editable').forEach(function (el) {
          el.setAttribute('contenteditable', 'true');
          el.setAttribute('tabindex', '0');
        });
        showToast('info', 'Editing Profile', 'Click any field to edit your information.');
      } else {
        profileEditToggle.innerHTML = '<i class="fas fa-pen"></i> Edit Profile';
        profileEditToggle.style.background = 'rgba(255,255,255,0.2)';
        profileEditToggle.style.borderColor = 'rgba(255,255,255,0.3)';
        if (saveProfileBtn) saveProfileBtn.style.display = 'none';
        document.querySelectorAll('.profile-page .editable').forEach(function (el) {
          el.removeAttribute('contenteditable');
          el.removeAttribute('tabindex');
        });
        loadSavedEdits();
      }
    });
  }

  if (saveProfileBtn) {
    saveProfileBtn.addEventListener('click', function () {
      saveEdits();
      profileEditToggle.innerHTML = '<i class="fas fa-pen"></i> Edit Profile';
      profileEditToggle.style.background = 'rgba(255,255,255,0.2)';
      profileEditToggle.style.borderColor = 'rgba(255,255,255,0.3)';
      saveProfileBtn.style.display = 'none';
      document.body.classList.remove('edit-mode-active');
      document.querySelectorAll('.profile-page .editable').forEach(function (el) {
        el.removeAttribute('contenteditable');
        el.removeAttribute('tabindex');
      });
      var nameEl = document.querySelector('.profile-page .editable[data-key="profileName"]');
      if (nameEl) localStorage.setItem('voterName', nameEl.textContent);
      showToast('success', 'Profile Updated', 'Your profile information has been saved successfully.');
    });
  }

});
