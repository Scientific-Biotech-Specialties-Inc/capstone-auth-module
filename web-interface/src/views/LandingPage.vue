<template>
  <div class="lp-container">
    <!-- TOP NAVIGATION BAR -->
    <header class="lp-nav">
      <div class="lp-nav-inner">
        <!-- Left: Corporate Logo -->
        <a href="#hero" class="lp-nav-logo" @click.prevent="scrollToSection('hero')" aria-label="SBSI Home">
          <img src="/images/sbsi-logo-variation1-white.webp" alt="Scientific Biotech Specialties, Inc." class="lp-logo-img" />
        </a>

        <!-- Right: Links (Home, Systems, Features) + Login Button -->
        <div class="lp-nav-right">
          <nav class="lp-nav-links" aria-label="Main Navigation">
            <a 
              href="#hero" 
              class="lp-nav-link" 
              :class="{ 'is-active': activeSection === 'hero' }"
              @click.prevent="scrollToSection('hero')"
            >
              Home
            </a>
            <a 
              href="#systems" 
              class="lp-nav-link" 
              :class="{ 'is-active': activeSection === 'systems' }"
              @click.prevent="scrollToSection('systems')"
            >
              Systems
            </a>
            <a 
              href="#features" 
              class="lp-nav-link" 
              :class="{ 'is-active': activeSection === 'features' }"
              @click.prevent="scrollToSection('features')"
            >
              Features
            </a>
          </nav>

          <div class="lp-nav-divider"></div>

          <div class="lp-nav-actions">
            <router-link to="/login" class="lp-btn-login">
              Log in
            </router-link>
          </div>
        </div>
      </div>
    </header>

    <!-- SECTION 1: HERO SECTION (Home) -->
    <section id="hero" class="lp-hero">
      <div class="lp-hero-bg"></div>
      <div class="lp-hero-overlay"></div>
      
      <div class="lp-hero-content">
        <h1 class="lp-hero-title">
          25 Years of Innovating Diagnostics Solutions
        </h1>

        <div class="lp-hero-divider"></div>

        <p class="lp-hero-desc">
          A unified platform for managing contracts, expenses, productivity and support — built for the business finance and marketing industry.
        </p>

        <div class="lp-hero-ctas">
          <router-link to="/login" class="lp-cta-primary">
            <span>Get Started</span>
            <ArrowRight :size="18" />
          </router-link>
        </div>
      </div>
    </section>

    <!-- SECTION 2: SLIDES OF THE 4 SYSTEMS (Systems) -->
    <section id="systems" class="lp-slides-section">
      <div class="lp-section-container">
        <!-- Section Header -->
        <div class="lp-section-head">
          <span class="lp-eyebrow">Enterprise Ecosystem</span>
          <h2 class="lp-section-title">Explore the 4 Connected Systems</h2>
          <p class="lp-section-subtitle">
            Integrated identity authority and single sign-on connecting legal, financial, productivity, and support operations.
          </p>
        </div>

        <!-- Slide Presentation Carousel Viewport (auto-rotate with hover pause & navigation arrows) -->
        <div 
          class="lp-carousel-card" 
          @mouseenter="handleMouseEnter" 
          @mouseleave="handleMouseLeave"
        >
          <!-- Left Navigation Arrow (revealed on hover) -->
          <button 
            type="button"
            class="lp-carousel-arrow lp-carousel-arrow-prev" 
            aria-label="Previous system slide"
            @click="handlePrev"
          >
            <ChevronLeft :size="20" />
          </button>

          <!-- Right Navigation Arrow (revealed on hover) -->
          <button 
            type="button"
            class="lp-carousel-arrow lp-carousel-arrow-next" 
            aria-label="Next system slide"
            @click="handleNext"
          >
            <ChevronRight :size="20" />
          </button>

          <!-- Current Slide Transition Frame -->
          <transition name="slide-fade" mode="out-in">
            <div 
              :key="activeSlide.id" 
              class="lp-slide-content"
              :style="{ '--slide-theme': activeSlide.accentColor }"
            >
              <!-- Left Column: Details & Capabilities -->
              <div class="lp-slide-text">
                <div class="lp-slide-pill" :style="{ backgroundColor: activeSlide.lightBg, color: activeSlide.accentColor }">
                  <span class="lp-pill-dot" :style="{ backgroundColor: activeSlide.accentColor }"></span>
                  <span>{{ activeSlide.acronym }} • {{ activeSlide.category }}</span>
                </div>

                <h3 class="lp-slide-name">{{ activeSlide.fullName }}</h3>
                <p class="lp-slide-tagline">{{ activeSlide.tagline }}</p>
                <p class="lp-slide-desc">{{ activeSlide.detailedDesc }}</p>

                <!-- Key Capabilities Checkmarks -->
                <div class="lp-capabilities-grid">
                  <div 
                    v-for="(feat, fIdx) in activeSlide.keyFeatures" 
                    :key="fIdx" 
                    class="lp-capability-item"
                  >
                    <CheckCircle2 :size="15" :style="{ color: activeSlide.accentColor }" />
                    <span>{{ feat }}</span>
                  </div>
                </div>

                <!-- Live Metrics Summary -->
                <div class="lp-slide-metrics">
                  <div 
                    v-for="(stat, sIdx) in activeSlide.metrics" 
                    :key="sIdx" 
                    class="lp-metric-box"
                  >
                    <span class="lp-metric-val">{{ stat.val }}</span>
                    <span class="lp-metric-lbl">{{ stat.lbl }}</span>
                  </div>
                </div>

                <!-- Action CTA -->
                <div class="lp-slide-action">
                  <router-link to="/login" class="lp-slide-btn" :style="{ backgroundColor: activeSlide.accentColor }">
                    <span>Access {{ activeSlide.acronym }} Module</span>
                    <ArrowRight :size="15" />
                  </router-link>
                  <span class="lp-sso-note">SSO Authorized • RBAC Governed</span>
                </div>
              </div>

              <!-- Right Column: Interactive UI Mockup Graphic -->
              <div class="lp-slide-visual">
                <div class="lp-mock-window">
                  <!-- Window Header -->
                  <div class="lp-mock-topbar">
                    <div class="lp-mock-dots">
                      <span class="lp-mock-dot red"></span>
                      <span class="lp-mock-dot yellow"></span>
                      <span class="lp-mock-dot green"></span>
                    </div>
                    <div class="lp-mock-address">
                      <Lock :size="10" />
                      <span>sbsi.internal/{{ activeSlide.slug }}</span>
                    </div>
                    <div class="lp-mock-badge" :style="{ backgroundColor: activeSlide.lightBg, color: activeSlide.accentColor }">
                      {{ activeSlide.badgeText }}
                    </div>
                  </div>

                  <!-- Mock Window Body -->
                  <div class="lp-mock-canvas">
                    <!-- Top stats strip -->
                    <div class="lp-mock-stats-row">
                      <div class="lp-mock-stat-chip">
                        <span class="lp-m-chip-lbl">Status</span>
                        <span class="lp-m-chip-val" :style="{ color: activeSlide.accentColor }">Live • Operational</span>
                      </div>
                      <div class="lp-mock-stat-chip">
                        <span class="lp-m-chip-lbl">Audit Coverage</span>
                        <span class="lp-m-chip-val text-emerald-600">100% Verified</span>
                      </div>
                    </div>

                    <!-- SVG Chart / Analytics Visualization -->
                    <div class="lp-mock-chart-container">
                      <div class="lp-mock-chart-header">
                        <span class="lp-mock-chart-title">{{ activeSlide.chartTitle }}</span>
                        <span class="lp-mock-chart-trend" :style="{ color: activeSlide.accentColor }">+18.4% vs last period</span>
                      </div>

                      <svg class="lp-mock-chart-svg" viewBox="0 0 380 120" preserveAspectRatio="none">
                        <defs>
                          <linearGradient :id="`grad-${activeSlide.id}`" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" :stop-color="activeSlide.accentColor" stop-opacity="0.32" />
                            <stop offset="100%" :stop-color="activeSlide.accentColor" stop-opacity="0.0" />
                          </linearGradient>
                        </defs>
                        <!-- Grid Lines -->
                        <line x1="0" y1="30" x2="380" y2="30" stroke="#f1f5f9" stroke-width="1" />
                        <line x1="0" y1="65" x2="380" y2="65" stroke="#f1f5f9" stroke-width="1" />
                        <line x1="0" y1="100" x2="380" y2="100" stroke="#f1f5f9" stroke-width="1" />
                        
                        <!-- Area Fill -->
                        <path :d="activeSlide.chartPathFill" :fill="`url(#grad-${activeSlide.id})`" />
                        <!-- Stroke Line -->
                        <path :d="activeSlide.chartPathStroke" fill="none" :stroke="activeSlide.accentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                        
                        <!-- Accent Data Dots -->
                        <circle cx="95" cy="48" r="4" :fill="activeSlide.accentColor" stroke="#fff" stroke-width="2" />
                        <circle cx="190" cy="32" r="4" :fill="activeSlide.accentColor" stroke="#fff" stroke-width="2" />
                        <circle cx="285" cy="52" r="4" :fill="activeSlide.accentColor" stroke="#fff" stroke-width="2" />
                        <circle cx="370" cy="24" r="5" :fill="activeSlide.accentColor" stroke="#fff" stroke-width="2" />
                      </svg>
                    </div>

                    <!-- Mini Activity Table / Feed List -->
                    <div class="lp-mock-feed">
                      <div 
                        v-for="(item, idx) in activeSlide.recentItems" 
                        :key="idx" 
                        class="lp-mock-feed-item"
                      >
                        <div class="lp-mock-feed-left">
                          <span class="lp-mock-feed-dot" :style="{ backgroundColor: activeSlide.accentColor }"></span>
                          <span class="lp-mock-feed-title">{{ item.title }}</span>
                        </div>
                        <span class="lp-mock-feed-status" :class="item.statusClass">{{ item.status }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Floating Inspection Badge Overlay -->
                  <div class="lp-mock-lens-badge" :style="{ borderColor: activeSlide.accentColor }">
                    <Sparkles :size="12" :style="{ color: activeSlide.accentColor }" />
                    <span>Real-Time One UI Engine</span>
                  </div>
                </div>
              </div>
            </div>
          </transition>

          <!-- Slide Carousel Indicators -->
          <div class="lp-slide-dots">
            <button
              v-for="(_, dIdx) in coreSystems"
              :key="dIdx"
              class="lp-dot-btn"
              :class="{ 'is-active': currentSlideIndex === dIdx }"
              :aria-label="`Go to slide ${dIdx + 1}`"
              @click="setSlide(dIdx)"
            ></button>
          </div>
        </div>
      </div>
    </section>

    <!-- SECTION 3: EVERYTHING YOUR TEAM NEEDS (Features) -->
    <section id="features" class="lp-cards-section">
      <div class="lp-section-container">
        <!-- Section Heading -->
        <div class="lp-section-head text-center">
          <span class="lp-eyebrow">Comprehensive Features</span>
          <h2 class="lp-section-title">Everything your team needs</h2>
          <p class="lp-section-subtitle">
            Four integrated systems designed to streamline your operations from end to end.
          </p>
        </div>

        <!-- The Row of System Cards -->
        <div class="lp-system-cards-grid">
          <div
            v-for="sys in allSystemCards"
            :key="sys.id"
            class="lp-sys-card"
            :style="{ 
              '--bottom-accent': sys.accentColor,
              '--card-glow': sys.lightBg 
            }"
          >
            <!-- Ambient Card Background Blob -->
            <div class="lp-sys-ambient-blob"></div>

            <!-- Top Icon Container -->
            <div 
              class="lp-sys-icon-box" 
              :style="{ backgroundColor: sys.lightBg, color: sys.accentColor }"
            >
              <component :is="sys.icon" :size="24" />
            </div>

            <!-- Card Body Content -->
            <div class="lp-sys-body">
              <h4 class="lp-sys-title">{{ sys.title }}</h4>
              <p class="lp-sys-desc">{{ sys.desc }}</p>
            </div>

            <!-- Quick Action Link / Button -->
            <router-link to="/login" class="lp-sys-action-link" :style="{ color: sys.accentColor }">
              <span>Explore {{ sys.shortTitle }}</span>
              <ArrowRight :size="13" />
            </router-link>

            <!-- Bottom Color Accent Strip -->
            <div class="lp-sys-bottom-accent"></div>
          </div>
        </div>
      </div>
    </section>

    <!-- FOOTER (Enhanced ONE UI Compliant) -->
    <footer class="lp-footer">
      <div class="lp-footer-container">
        <!-- Main Footer Columns Grid -->
        <div class="lp-footer-grid">
          <!-- Column 1: Corporate Identity & Brand Mark -->
          <div class="lp-footer-brand-col">
            <a href="#hero" class="lp-footer-logo-link" @click.prevent="scrollToSection('hero')" aria-label="SBSI Home">
              <img src="/images/sbsi-logo-variation1-white.webp" alt="Scientific Biotech Specialties, Inc." class="lp-footer-logo" />
            </a>
            <div class="lp-footer-badge">
              <span class="lp-footer-badge-dot"></span>
              <span>ISO 9001:2015 Certified Enterprise</span>
            </div>
          </div>

          <!-- Column 2: Connected Ecosystem -->
          <div class="lp-footer-col">
            <h4 class="lp-footer-col-title">Connected Ecosystem</h4>
            <ul class="lp-footer-list">
              <li>
                <router-link to="/login" class="lp-footer-link-item">
                  Contract Management System
                </router-link>
              </li>
              <li>
                <router-link to="/login" class="lp-footer-link-item">
                  Smart Expense Reimbursement Management System
                </router-link>
              </li>
              <li>
                <router-link to="/login" class="lp-footer-link-item">
                  Productivity Report System
                </router-link>
              </li>
              <li>
                <router-link to="/login" class="lp-footer-link-item">
                  Ticketing System
                </router-link>
              </li>
            </ul>
          </div>

          <!-- Column 3: Navigation -->
          <div class="lp-footer-col">
            <h4 class="lp-footer-col-title">Navigation</h4>
            <ul class="lp-footer-list">
              <li>
                <a href="#hero" class="lp-footer-link-item" @click.prevent="scrollToSection('hero')">
                  Home Overview
                </a>
              </li>
              <li>
                <a href="#systems" class="lp-footer-link-item" @click.prevent="scrollToSection('systems')">
                  Explore 4 Systems
                </a>
              </li>
              <li>
                <a href="#features" class="lp-footer-link-item" @click.prevent="scrollToSection('features')">
                  Platform Features
                </a>
              </li>
              <li>
                <router-link to="/login" class="lp-footer-link-item lp-footer-link-accent">
                  Access Portal
                </router-link>
              </li>
            </ul>
          </div>

          <!-- Column 4: Security & Governance -->
          <div class="lp-footer-col">
            <h4 class="lp-footer-col-title">Security & Authority</h4>
            <ul class="lp-footer-list">
              <li class="lp-footer-security-item">
                <ShieldCheck :size="16" class="lp-footer-sec-icon" />
                <span>OAuth2 / OpenID Connect SSO</span>
              </li>
              <li class="lp-footer-security-item">
                <Lock :size="16" class="lp-footer-sec-icon" />
                <span>Role-Based Access Control (RBAC)</span>
              </li>
              <li class="lp-footer-security-item">
                <CheckCircle2 :size="16" class="lp-footer-sec-icon" />
                <span>Comprehensive Audit Trail</span>
              </li>
            </ul>
          </div>
        </div>

        <!-- Bottom Copyright & Legal Strip -->
        <div class="lp-footer-bottom">
          <span class="lp-footer-copy">
            © 2026 Scientific Biotech Specialties, Inc. (SBSI). All rights reserved.
          </span>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import {
  FileText,
  Receipt,
  BarChart3,
  Ticket,
  Workflow,
  ArrowRight,
  CheckCircle2,
  Lock,
  Sparkles,
  ShieldCheck,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next';

// ==========================================
// 1. DATA MODELS & CONFIGURATION
// ==========================================

export interface SystemItem {
  id: string;
  slug: string;
  title: string;
  shortTitle: string;
  fullName: string;
  acronym: string;
  category: string;
  icon: any;
  accentColor: string;
  lightBg: string;
  tagline: string;
  desc: string;
  detailedDesc: string;
  keyFeatures: string[];
  metrics: { val: string; lbl: string }[];
  badgeText: string;
  chartTitle: string;
  chartPathFill: string;
  chartPathStroke: string;
  recentItems: { title: string; status: string; statusClass: string }[];
}

// The 4 Core Subsystems as defined in AGENTS.md + 5th Workflow Automation
const coreSystems: SystemItem[] = [
  {
    id: 'crms',
    slug: 'cms',
    title: 'Contract Management',
    shortTitle: 'Contracts',
    fullName: 'Contract Management System',
    acronym: 'CRMS',
    category: 'Legal & Agreement Governance',
    icon: FileText,
    accentColor: '#2E85D8',
    lightBg: '#EFF6FF',
    tagline: 'End-to-End Legal Agreement & Contract Lifecycle Management',
    desc: 'Track, manage, and renew contracts with full audit trails and approval workflows.',
    detailedDesc:
      'Centralize document drafting, legal audits, automated expiration reminders, and departmental multi-tier review pipelines for complete institutional accountability.',
    keyFeatures: [
      'Digital Signature & Verification Trails',
      'Automated Renewal & Expiration Alerts',
      'Multi-Tier Departmental Approvals',
      'Central Legal Document Repository',
    ],
    metrics: [
      { val: '142', lbl: 'Active Agreements' },
      { val: '100%', lbl: 'Audit Trail Coverage' },
      { val: '< 48h', lbl: 'Approval Turnaround' },
    ],
    badgeText: 'Contract Engine',
    chartTitle: 'Contract Lifecycle & Renewal Velocity',
    chartPathFill: 'M 0 100 L 0 75 Q 95 30 190 60 T 380 20 L 380 120 L 0 120 Z',
    chartPathStroke: 'M 0 75 Q 95 30 190 60 T 380 20',
    recentItems: [
      { title: 'Reagent Supply Master Agreement #2026-88', status: 'Approved', statusClass: 'status-approved' },
      { title: 'Equipment Maintenance SLA Renewal (Q3)', status: 'Pending Review', statusClass: 'status-pending' },
      { title: 'Hospital Diagnostic Partner Contract', status: 'Active', statusClass: 'status-approved' },
    ],
  },
  {
    id: 'serms',
    slug: 'serms',
    title: 'Smart Expense Reimbursement',
    shortTitle: 'Expenses',
    fullName: 'Smart Expense Reimbursement Management System',
    acronym: 'SERMS',
    category: 'Financial Claims & Disbursals',
    icon: Receipt,
    accentColor: '#10B981',
    lightBg: '#ECFDF5',
    tagline: 'Automated Financial Claims, Receipts & Expense Governance',
    desc: 'Smart expense submissions with real-time status tracking and automated approvals.',
    detailedDesc:
      'Streamline expense submissions, receipt scanning, and policy validation with real-time status tracking, tax compliance checks, and rapid disbursement routing.',
    keyFeatures: [
      'Instant OCR & Digital Receipt Capture',
      'Automated Policy & Budget Enforcement',
      'Real-Time Multi-Level Audit Trails',
      'Rapid Disbursement & ERP Exporting',
    ],
    metrics: [
      { val: '₱284.5K', lbl: 'Disbursed This Month' },
      { val: '< 24h', lbl: 'Avg Approval Cycle' },
      { val: '99.9%', lbl: 'Receipt OCR Accuracy' },
    ],
    badgeText: 'Finance Engine',
    chartTitle: 'Monthly Expense Submissions & Approvals',
    chartPathFill: 'M 0 110 L 0 85 Q 95 45 190 35 T 380 15 L 380 120 L 0 120 Z',
    chartPathStroke: 'M 0 85 Q 95 45 190 35 T 380 15',
    recentItems: [
      { title: 'Diagnostic Field Reagents Logistics #EX-401', status: 'Approved', statusClass: 'status-approved' },
      { title: 'Client Site Inspection Travel Per Diem', status: 'Approved', statusClass: 'status-approved' },
      { title: 'Laboratory Quality Certification Fees', status: 'Under Review', statusClass: 'status-pending' },
    ],
  },
  {
    id: 'prs',
    slug: 'prs',
    title: 'Productivity Reports',
    shortTitle: 'Productivity',
    fullName: 'Productivity Report System',
    acronym: 'PRS',
    category: 'Operational Efficiency & Output',
    icon: BarChart3,
    accentColor: '#8B5CF6',
    lightBg: '#F5F3FF',
    tagline: 'Comprehensive Operational Efficiency & KPI Metrics',
    desc: 'Generate detailed productivity analytics and performance summaries across teams.',
    detailedDesc:
      'Interactive dashboards for departmental oversight. Generate high-impact visual reports on KPIs, operational efficiency, and team output for strategic decision-making.',
    keyFeatures: [
      'Interactive Team & Individual KPI Dashboards',
      'Automated Weekly & Monthly Summaries',
      'Supervisor Validation Queue Workflows',
      'Exportable Executive PDF & Excel Reports',
    ],
    metrics: [
      { val: '96.4%', lbl: 'Operational Efficiency' },
      { val: '1,280', lbl: 'Validated Submissions' },
      { val: '+14.2%', lbl: 'Department Output' },
    ],
    badgeText: 'Analytics Engine',
    chartTitle: 'Operational Output & Team Performance',
    chartPathFill: 'M 0 105 L 0 60 Q 95 65 190 28 T 380 22 L 380 120 L 0 120 Z',
    chartPathStroke: 'M 0 60 Q 95 65 190 28 T 380 22',
    recentItems: [
      { title: 'Marketing Q2 Performance Scorecard', status: 'Validated', statusClass: 'status-approved' },
      { title: 'Biotech Field Specialist Activity Logs', status: 'Validated', statusClass: 'status-approved' },
      { title: 'Technical Services Weekly Output Index', status: 'Pending Review', statusClass: 'status-pending' },
    ],
  },
  {
    id: 'ts',
    slug: 'ts',
    title: 'Ticketing System',
    shortTitle: 'Support & SLA',
    fullName: 'Ticketing System',
    acronym: 'TS',
    category: 'Enterprise Helpdesk & SLA Support',
    icon: Ticket,
    accentColor: '#F59E0B',
    lightBg: '#FFFBEB',
    tagline: 'Enterprise Helpdesk, Support Queuing & SLA Resolution',
    desc: 'Submit, escalate with SLA breach monitoring built in.',
    detailedDesc:
      'A centralized engine for internal and client support requests. Resolve issues efficiently with priority-based queuing, automated SLA escalation, and full knowledge base integration.',
    keyFeatures: [
      'Automated Priority-Based Ticket Routing',
      'Live SLA Breach Countdown Timers',
      'Centralized Knowledge Base Integration',
      'Customer Satisfaction (CSAT) Metrics',
    ],
    metrics: [
      { val: '18 mins', lbl: 'Avg First Response' },
      { val: '99.4%', lbl: 'SLA Adherence Rate' },
      { val: '4.9 / 5', lbl: 'Customer Rating' },
    ],
    badgeText: 'Helpdesk Engine',
    chartTitle: 'Ticket Volume Resolution & Response Times',
    chartPathFill: 'M 0 115 L 0 70 Q 95 50 190 40 T 380 25 L 380 120 L 0 120 Z',
    chartPathStroke: 'M 0 70 Q 95 50 190 40 T 380 25',
    recentItems: [
      { title: 'Reagent Analyzer Firmware Calibration #TS-892', status: 'Resolved', statusClass: 'status-approved' },
      { title: 'User Access Permission Escalation #TS-893', status: 'In Progress', statusClass: 'status-pending' },
      { title: 'Laboratory Network Interface Diagnostic', status: 'Resolved', statusClass: 'status-approved' },
    ],
  },
];

// 5 cards for Section 3
const allSystemCards: SystemItem[] = [
  ...coreSystems,
  {
    id: 'workflow',
    slug: 'workflow',
    title: 'Workflow Automation',
    shortTitle: 'Automation',
    fullName: 'Enterprise Workflow Automation Engine',
    acronym: 'WFA',
    category: 'Cross-System Orchestration',
    icon: Workflow,
    accentColor: '#06B6D4',
    lightBg: '#ECFEFF',
    tagline: 'Orchestrate Business Rules Across All 4 Group Systems',
    desc: 'Design, implement, and manage automated business processes across all your software and services with integrated tools.',
    detailedDesc:
      'Connect CRMS, SERMS, PRS, and TS through unified background triggers, webhook notifications, and automated synchronization pipelines.',
    keyFeatures: [
      'Cross-System Event Triggers',
      'Automated Scheduled Batch Jobs',
      'Configurable Business Logic Rules',
      'Real-Time Health Monitoring',
    ],
    metrics: [
      { val: '8.4K', lbl: 'Automated Runs / Day' },
      { val: '34', lbl: 'Active Pipelines' },
      { val: '99.98%', lbl: 'Engine Uptime' },
    ],
    badgeText: 'Automation Engine',
    chartTitle: 'Automated Job Execution & Throughput',
    chartPathFill: 'M 0 110 L 0 80 Q 95 35 190 50 T 380 18 L 380 120 L 0 120 Z',
    chartPathStroke: 'M 0 80 Q 95 35 190 50 T 380 18',
    recentItems: [
      { title: 'Weekly Expense to PRS Metric Sync Pipeline', status: 'Completed', statusClass: 'status-approved' },
      { title: 'Contract Renewal Notification Dispatcher', status: 'Active', statusClass: 'status-approved' },
      { title: 'Daily SLA Status Broadcast Service', status: 'Completed', statusClass: 'status-approved' },
    ],
  },
];

// ==========================================
// 2. NAVIGATION & ACTIVE SECTION STATE
// ==========================================

const activeSection = ref<'hero' | 'systems' | 'features'>('hero');

function scrollToSection(sectionId: 'hero' | 'systems' | 'features') {
  activeSection.value = sectionId;
  const targetElement = document.getElementById(sectionId);
  if (targetElement) {
    targetElement.scrollIntoView({ behavior: 'smooth' });
  }
}

let scrollObserver: IntersectionObserver | null = null;

function setupScrollObserver() {
  const options = {
    root: null,
    rootMargin: '-20% 0px -60% 0px',
    threshold: 0,
  };

  scrollObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const id = entry.target.id as 'hero' | 'systems' | 'features';
        if (id) {
          activeSection.value = id;
        }
      }
    });
  }, options);

  const heroEl = document.getElementById('hero');
  const systemsEl = document.getElementById('systems');
  const featuresEl = document.getElementById('features');

  if (heroEl) scrollObserver.observe(heroEl);
  if (systemsEl) scrollObserver.observe(systemsEl);
  if (featuresEl) scrollObserver.observe(featuresEl);
}

// ==========================================
// 3. SECTION 2: SLIDES CAROUSEL STATE
// ==========================================

const currentSlideIndex = ref(0);
const isCarouselHovered = ref(false);
let slideTimer: any = null;

const activeSlide = computed(() => {
  return coreSystems[currentSlideIndex.value] || coreSystems[0];
});

function prevSlide() {
  currentSlideIndex.value = (currentSlideIndex.value - 1 + coreSystems.length) % coreSystems.length;
}

function nextSlide() {
  currentSlideIndex.value = (currentSlideIndex.value + 1) % coreSystems.length;
}

function handlePrev() {
  prevSlide();
  if (!isCarouselHovered.value) {
    startSlideTimer();
  }
}

function handleNext() {
  nextSlide();
  if (!isCarouselHovered.value) {
    startSlideTimer();
  }
}

function setSlide(index: number) {
  currentSlideIndex.value = index;
  if (!isCarouselHovered.value) {
    startSlideTimer();
  }
}

// 5 seconds interval for smooth, noticeable auto-rotation
function startSlideTimer() {
  stopSlideTimer();
  slideTimer = setInterval(() => {
    nextSlide();
  }, 5000);
}

function stopSlideTimer() {
  if (slideTimer) {
    clearInterval(slideTimer);
    slideTimer = null;
  }
}

function handleMouseEnter() {
  isCarouselHovered.value = true;
  stopSlideTimer();
}

function handleMouseLeave() {
  isCarouselHovered.value = false;
  startSlideTimer();
}

// ==========================================
// 4. LIFECYCLE HOOKS
// ==========================================

onMounted(() => {
  startSlideTimer();
  setupScrollObserver();
});

onUnmounted(() => {
  stopSlideTimer();
  if (scrollObserver) {
    scrollObserver.disconnect();
    scrollObserver = null;
  }
});
</script>

<style scoped>
/* ==========================================================================
   ONE UI DESIGN SYSTEM ENFORCEMENT (DESIGN.md)
   Brand Primary: #252578 | Brand Accent: #2E85D8 | Brand Deep: #3A3A85
   Brand Deep Border: #4E4E91 | Dark Slate: #1E293B | Muted Slate: #8D8B8F
   ========================================================================== */

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

.lp-container {
  font-family: 'Poppins', 'Montserrat', -apple-system, BlinkMacSystemFont, sans-serif;
  background-color: #f8f9fa;
  color: #1e293b;
  min-height: 100vh;
  overflow-x: hidden;
  scroll-behavior: smooth;
}

.lp-section-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 24px;
}

/* --------------------------------------------------------------------------
   NAVIGATION BAR
   Right aligned links: Home, Systems, Features (Brand Deep active token)
   -------------------------------------------------------------------------- */
.lp-nav {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 64px;
  z-index: 1000;
  background-color: rgba(37, 37, 120, 0.95);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}

.lp-nav-inner {
  max-width: 1400px;
  height: 100%;
  margin: 0 auto;
  padding: 0 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.lp-nav-logo {
  display: flex;
  align-items: center;
  text-decoration: none;
}

.lp-logo-img {
  height: 26px;
  object-fit: contain;
  transition: transform 0.2s ease;
}

.lp-logo-img:hover {
  transform: scale(1.03);
}

/* Right Side: Links & Login CTA grouped together */
.lp-nav-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.lp-nav-links {
  display: flex;
  align-items: center;
  gap: 6px;
}

.lp-nav-link {
  color: rgba(255, 255, 255, 0.75);
  font-size: 13.5px;
  font-weight: 500;
  text-decoration: none;
  cursor: pointer;
  padding: 6px 14px;
  border-radius: 8px;
  border: 1px solid transparent;
  transition: all 0.2s ease;
}

.lp-nav-link:hover {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.12);
}

/* ONE UI Specification: Brand Deep #3A3A85 with Brand Deep Border #4E4E91 */
.lp-nav-link.is-active {
  color: #ffffff;
  background-color: #3a3a85;
  border-color: #4e4e91;
  font-weight: 600;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.18);
}

.lp-nav-divider {
  width: 1px;
  height: 24px;
  background-color: rgba(255, 255, 255, 0.18);
}

.lp-btn-login {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background-color: #2e85d8;
  color: #ffffff;
  font-size: 13px;
  font-weight: 600;
  padding: 8px 22px;
  border-radius: 8px;
  text-decoration: none;
  transition: all 0.2s ease;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
}

.lp-btn-login:hover {
  background-color: #1e1e66;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(46, 133, 216, 0.35);
}

.lp-btn-login:active {
  transform: scale(0.98);
}

/* --------------------------------------------------------------------------
   SECTION 1: HERO SECTION
   -------------------------------------------------------------------------- */
.lp-hero {
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  padding-top: 64px;
}

.lp-hero-bg {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  background-image: url('/images/SBSI-bg.webp');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  z-index: 1;
}

.lp-hero-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    180deg,
    rgba(37, 37, 120, 0.52) 0%,
    rgba(25, 25, 85, 0.42) 45%,
    rgba(14, 14, 46, 0.68) 100%
  );
  z-index: 2;
}

.lp-hero-content {
  position: relative;
  z-index: 3;
  max-width: 1000px;
  margin: 0 auto;
  padding: 80px 24px 60px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.lp-hero-title {
  color: #ffffff;
  font-size: clamp(34px, 5.5vw, 64px);
  font-weight: 800;
  line-height: 1.18;
  letter-spacing: -0.02em;
  margin-bottom: 20px;
  text-shadow: 0 3px 12px rgba(0, 0, 0, 0.5);
  text-align: center;
}

.lp-hero-divider {
  width: 180px;
  height: 4px;
  background-color: #2e85d8;
  border-radius: 2px;
  margin-bottom: 24px;
  box-shadow: 0 2px 8px rgba(46, 133, 216, 0.6);
  align-self: center;
}

.lp-hero-desc {
  color: rgba(255, 255, 255, 0.95);
  font-size: clamp(15px, 2vw, 18px);
  line-height: 1.7;
  max-width: 680px;
  margin-bottom: 36px;
  font-weight: 400;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
  text-align: center;
}

.lp-hero-ctas {
  display: flex;
  gap: 16px;
  justify-content: center;
}

.lp-cta-primary {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  padding: 14px 40px;
  font-size: 15px;
  font-weight: 600;
  color: #ffffff;
  background-color: #2e85d8;
  border-radius: 9999px;
  text-decoration: none;
  box-shadow: 0 4px 16px rgba(46, 133, 216, 0.5);
  transition: all 0.25s ease;
}

.lp-cta-primary:hover {
  background-color: #252578;
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(37, 37, 120, 0.5);
}

.lp-cta-primary:active {
  transform: scale(0.98);
}

/* --------------------------------------------------------------------------
   SHARED SECTION HEADERS
   -------------------------------------------------------------------------- */
.lp-section-head {
  margin-bottom: 32px;
}

.lp-eyebrow {
  display: inline-block;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: #2e85d8;
  margin-bottom: 8px;
}

.lp-section-title {
  font-size: clamp(26px, 3.5vw, 36px);
  font-weight: 800;
  color: #1e293b;
  letter-spacing: -0.02em;
  line-height: 1.25;
  margin-bottom: 10px;
}

.lp-section-subtitle {
  color: #8d8b8f;
  font-size: 15px;
  max-width: 680px;
  line-height: 1.6;
}

.text-center .lp-section-subtitle {
  margin: 0 auto;
}

/* --------------------------------------------------------------------------
   SECTION 2: SLIDES OF THE 4 SYSTEMS (Compact & refined)
   -------------------------------------------------------------------------- */
.lp-slides-section {
  padding: 96px 0;
  background: #ffffff;
  border-bottom: 1px solid #e2e8f0;
}

.lp-carousel-card {
  position: relative;
  max-width: 1060px;
  margin: 0 auto;
  background: #f8f9fa;
  border: 1px solid #e2e8f0;
  border-radius: 18px;
  padding: 28px 48px;
  box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

/* --------------------------------------------------------------------------
   Carousel Navigation Arrows (Revealed on Hover)
   -------------------------------------------------------------------------- */
.lp-carousel-arrow {
  position: absolute;
  top: 50%;
  z-index: 25;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  color: #1e293b;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.25s ease, transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
}

.lp-carousel-arrow-prev {
  left: 14px;
  transform: translate(-8px, -50%);
}

.lp-carousel-arrow-next {
  right: 14px;
  transform: translate(8px, -50%);
}

/* Reveal arrows when carousel card is hovered */
.lp-carousel-card:hover .lp-carousel-arrow {
  opacity: 1;
  pointer-events: auto;
}

.lp-carousel-card:hover .lp-carousel-arrow-prev {
  transform: translate(0, -50%);
}

.lp-carousel-card:hover .lp-carousel-arrow-next {
  transform: translate(0, -50%);
}

/* Arrow button hover & active states */
.lp-carousel-arrow:hover {
  background: #252578 !important;
  border-color: #252578 !important;
  color: #ffffff !important;
  box-shadow: 0 6px 18px rgba(37, 37, 120, 0.25);
}

.lp-carousel-card:hover .lp-carousel-arrow-prev:hover {
  transform: translate(0, -50%) scale(1.08);
}

.lp-carousel-card:hover .lp-carousel-arrow-next:hover {
  transform: translate(0, -50%) scale(1.08);
}

.lp-carousel-arrow:active {
  transform: translate(0, -50%) scale(0.95) !important;
}

/* Touch devices support */
@media (hover: none) {
  .lp-carousel-arrow {
    opacity: 0.85;
    pointer-events: auto;
  }
  .lp-carousel-arrow-prev {
    transform: translate(0, -50%);
  }
  .lp-carousel-arrow-next {
    transform: translate(0, -50%);
  }
}

/* Fluid Slide Transitions */
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.slide-fade-enter-from {
  opacity: 0;
  transform: translateY(8px);
}

.slide-fade-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

.lp-slide-content {
  display: grid;
  grid-template-columns: 1fr 0.88fr;
  gap: 28px;
  align-items: center;
}

.lp-slide-text {
  display: flex;
  flex-direction: column;
}

.lp-slide-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  align-self: flex-start;
  padding: 4px 10px;
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 700;
  margin-bottom: 10px;
}

.lp-pill-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
}

.lp-slide-name {
  font-size: clamp(20px, 2.2vw, 24px);
  font-weight: 800;
  color: #1e293b;
  letter-spacing: -0.01em;
  margin-bottom: 4px;
}

.lp-slide-tagline {
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  margin-bottom: 8px;
}

.lp-slide-desc {
  font-size: 12.5px;
  color: #64748b;
  line-height: 1.55;
  margin-bottom: 14px;
}

.lp-capabilities-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
  margin-bottom: 14px;
}

.lp-capability-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 500;
  color: #334155;
}

.lp-slide-metrics {
  display: flex;
  gap: 18px;
  padding: 8px 0;
  margin-bottom: 16px;
  border-top: 1px solid #e2e8f0;
  border-bottom: 1px solid #e2e8f0;
}

.lp-metric-box {
  display: flex;
  flex-direction: column;
}

.lp-metric-val {
  font-size: 18px;
  font-weight: 800;
  color: #1e293b;
}

.lp-metric-lbl {
  font-size: 10px;
  color: #8d8b8f;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 600;
}

.lp-slide-action {
  display: flex;
  align-items: center;
  gap: 12px;
}

.lp-slide-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 18px;
  border-radius: 7px;
  color: #ffffff;
  font-size: 12px;
  font-weight: 600;
  text-decoration: none;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
  transition: all 0.2s ease;
}

.lp-slide-btn:hover {
  transform: translateY(-1px);
  filter: brightness(1.1);
}

.lp-sso-note {
  font-size: 11px;
  color: #8d8b8f;
  font-weight: 500;
}

/* Slide Right Visual Window */
.lp-slide-visual {
  display: flex;
  justify-content: center;
}

.lp-mock-window {
  position: relative;
  width: 100%;
  max-width: 380px;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.06);
  overflow: hidden;
}

.lp-mock-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 6px 12px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.lp-mock-dots {
  display: flex;
  gap: 4px;
}

.lp-mock-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}
.lp-mock-dot.red { background: #ef4444; }
.lp-mock-dot.yellow { background: #f59e0b; }
.lp-mock-dot.green { background: #10b981; }

.lp-mock-address {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 10px;
  color: #64748b;
  background: #ffffff;
  padding: 2px 8px;
  border-radius: 4px;
  border: 1px solid #e2e8f0;
}

.lp-mock-badge {
  font-size: 9px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 4px;
  text-transform: uppercase;
}

.lp-mock-canvas {
  padding: 12px;
}

.lp-mock-stats-row {
  display: flex;
  gap: 8px;
  margin-bottom: 8px;
}

.lp-mock-stat-chip {
  flex: 1;
  background: #f8fafc;
  border: 1px solid #f1f5f9;
  border-radius: 6px;
  padding: 4px 8px;
  display: flex;
  flex-direction: column;
}

.lp-m-chip-lbl {
  font-size: 8.5px;
  color: #8d8b8f;
  text-transform: uppercase;
  font-weight: 600;
}

.lp-m-chip-val {
  font-size: 11px;
  font-weight: 700;
}

.lp-mock-chart-container {
  background: #ffffff;
  border: 1px solid #f1f5f9;
  border-radius: 8px;
  padding: 10px;
  margin-bottom: 8px;
}

.lp-mock-chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
}

.lp-mock-chart-title {
  font-size: 10.5px;
  font-weight: 700;
  color: #1e293b;
}

.lp-mock-chart-trend {
  font-size: 9.5px;
  font-weight: 600;
}

.lp-mock-chart-svg {
  width: 100%;
  height: 65px;
}

.lp-mock-feed {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.lp-mock-feed-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 4px 6px;
  background: #f8fafc;
  border-radius: 4px;
  font-size: 9.5px;
}

.lp-mock-feed-left {
  display: flex;
  align-items: center;
  gap: 5px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.lp-mock-feed-dot {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  flex-shrink: 0;
}

.lp-mock-feed-title {
  color: #334155;
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.lp-mock-feed-status {
  padding: 1px 6px;
  border-radius: 9999px;
  font-size: 9px;
  font-weight: 700;
  flex-shrink: 0;
}

.status-approved {
  background: #ecfdf5;
  color: #10b981;
}

.status-pending {
  background: #fffbeb;
  color: #f59e0b;
}

.lp-mock-lens-badge {
  position: absolute;
  bottom: 8px;
  right: 8px;
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(8px);
  border: 1px solid;
  border-radius: 9999px;
  padding: 3px 8px;
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 9px;
  font-weight: 700;
  color: #1e293b;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.lp-slide-dots {
  display: flex;
  justify-content: center;
  gap: 6px;
  margin-top: 18px;
}

.lp-dot-btn {
  width: 7px;
  height: 7px;
  border-radius: 9999px;
  background: #cbd5e1;
  border: none;
  cursor: pointer;
  transition: all 0.25s ease;
}

.lp-dot-btn.is-active {
  width: 20px;
  background: #252578;
  border-radius: 4px;
}

/* --------------------------------------------------------------------------
   SECTION 3: EVERYTHING YOUR TEAM NEEDS (Features)
   Occupies its own full section viewport with equal vertical padding
   -------------------------------------------------------------------------- */
.lp-cards-section {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 96px 0;
  background: #f8f9fa;
  position: relative;
  box-sizing: border-box;
}

.lp-system-cards-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 20px;
  margin-top: 24px;
}

.lp-sys-card {
  position: relative;
  background: #ffffff;
  border: 1.5px solid #e2e8f0;
  border-radius: 20px;
  padding: 32px 22px;
  min-height: 330px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
}

.lp-sys-ambient-blob {
  position: absolute;
  top: -20px;
  right: -20px;
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: var(--card-glow);
  filter: blur(24px);
  pointer-events: none;
  z-index: 0;
  transition: transform 0.4s ease;
}

.lp-sys-card:hover {
  transform: translateY(-8px);
  border-color: var(--bottom-accent);
  box-shadow: 0 16px 32px -6px rgba(0, 0, 0, 0.1);
}

.lp-sys-card:hover .lp-sys-ambient-blob {
  transform: scale(2.4);
}

.lp-sys-icon-box {
  position: relative;
  z-index: 1;
  width: 52px;
  height: 52px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.lp-sys-body {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex: 1;
}

.lp-sys-title {
  font-size: 17px;
  font-weight: 700;
  color: #1e293b;
  line-height: 1.3;
}

.lp-sys-desc {
  font-size: 13px;
  color: #64748b;
  line-height: 1.6;
}

.lp-sys-action-link {
  position: relative;
  z-index: 1;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  text-decoration: none;
  padding: 7px 14px;
  border-radius: 8px;
  border: 1px solid currentColor;
  background: rgba(255, 255, 255, 0.85);
  margin-top: auto;
  transition: all 0.2s ease;
}

.lp-sys-action-link:hover {
  background-color: var(--bottom-accent);
  color: #ffffff !important;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  transform: translateY(-1px);
}

.lp-sys-bottom-accent {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 5px;
  background-color: var(--bottom-accent);
}

/* --------------------------------------------------------------------------
   FOOTER (ONE UI DESIGN SYSTEM COMPLIANT)
   Brand Primary: #252578 | Brand Accent: #2E85D8 | Deep Navy: #101036
   -------------------------------------------------------------------------- */
.lp-footer {
  background-color: #101036;
  border-top: 3px solid #2e85d8;
  color: #ffffff;
  padding: 64px 0 32px;
}

.lp-footer-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 24px;
}

.lp-footer-grid {
  display: grid;
  grid-template-columns: 1.4fr 1fr 1fr 1.1fr;
  gap: 40px;
  margin-bottom: 48px;
}

.lp-footer-brand-col {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.lp-footer-logo-link {
  display: inline-block;
  text-decoration: none;
}

.lp-footer-logo {
  height: 32px;
  object-fit: contain;
  transition: opacity 0.2s ease;
}

.lp-footer-logo:hover {
  opacity: 0.9;
}

.lp-footer-brand-desc {
  font-size: 13px;
  color: #a7a7c8;
  line-height: 1.65;
  max-width: 360px;
}

.lp-footer-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 14px;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.16);
  border-radius: 9999px;
  font-size: 11.5px;
  font-weight: 600;
  color: #ffffff;
  width: fit-content;
  backdrop-filter: blur(8px);
}

.lp-footer-badge-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background-color: #10b981;
  box-shadow: 0 0 8px #10b981;
}

.lp-footer-col {
  display: flex;
  flex-direction: column;
}

.lp-footer-col-title {
  font-size: 12.5px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #ffffff;
  margin-bottom: 20px;
}

.lp-footer-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.lp-footer-link-item {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #a7a7c8;
  text-decoration: none;
  transition: all 0.2s ease;
}

.lp-footer-link-item:hover {
  color: #ffffff;
  transform: translateX(3px);
}

.lp-footer-link-accent {
  color: #2e85d8;
  font-weight: 600;
}

.lp-footer-link-accent:hover {
  color: #60a5fa;
}

.lp-footer-tag {
  padding: 2px 6px;
  border-radius: 4px;
  background: rgba(46, 133, 216, 0.16);
  border: 1px solid rgba(46, 133, 216, 0.3);
  color: #2e85d8;
  font-size: 10.5px;
  font-weight: 700;
  letter-spacing: 0.03em;
}

.lp-footer-security-item {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  color: #a7a7c8;
}

.lp-footer-sec-icon {
  color: #10b981;
  flex-shrink: 0;
}

.lp-footer-bottom {
  border-top: 1px solid rgba(255, 255, 255, 0.09);
  padding-top: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  width: 100%;
  font-size: 12px;
  color: rgba(167, 167, 200, 0.7);
}

.lp-footer-copy {
  font-weight: 400;
  text-align: center;
  width: 100%;
}

/* --------------------------------------------------------------------------
   ANIMATIONS & TRANSITIONS
   -------------------------------------------------------------------------- */
.slide-fade-enter-active {
  transition: all 0.35s ease-out;
}
.slide-fade-leave-active {
  transition: all 0.25s cubic-bezier(1, 0.5, 0.8, 1);
}
.slide-fade-enter-from {
  opacity: 0;
  transform: translateX(18px);
}
.slide-fade-leave-to {
  opacity: 0;
  transform: translateX(-18px);
}

/* --------------------------------------------------------------------------
   RESPONSIVE QUERIES
   -------------------------------------------------------------------------- */
@media (max-width: 1200px) {
  .lp-system-cards-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 900px) {
  .lp-slide-content {
    grid-template-columns: 1fr;
    gap: 24px;
  }

  .lp-system-cards-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .lp-footer-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 32px;
  }

  .lp-nav-links {
    gap: 4px;
  }
  .lp-nav-link {
    padding: 5px 10px;
    font-size: 12.5px;
  }
}

@media (max-width: 640px) {
  .lp-nav-links {
    display: none;
  }

  .lp-nav-divider {
    display: none;
  }

  .lp-slides-section {
    padding: 56px 0;
  }

  .lp-cards-section {
    min-height: auto;
    padding: 56px 0;
  }

  .lp-carousel-card {
    padding: 24px 18px;
  }

  .lp-carousel-arrow {
    width: 32px;
    height: 32px;
  }

  .lp-carousel-arrow-prev {
    left: 6px;
  }

  .lp-carousel-arrow-next {
    right: 6px;
  }

  .lp-capabilities-grid {
    grid-template-columns: 1fr;
  }

  .lp-system-cards-grid {
    grid-template-columns: 1fr;
  }

  .lp-footer-grid {
    grid-template-columns: 1fr;
    gap: 28px;
  }

  .lp-footer-bottom {
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    gap: 12px;
  }
}
</style>