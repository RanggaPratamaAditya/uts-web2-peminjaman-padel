<script setup>
import { computed, onMounted, ref } from 'vue'

const API = 'http://127.0.0.1:8000/api'

const token = ref(localStorage.getItem('token'))
const user = ref(
    JSON.parse(localStorage.getItem('user') || 'null')
)

const loading = ref(false)
const loadingData = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const activePage = ref('dashboard')
const showModal = ref(false)
const showLogoutModal = ref(false)

const loginForm = ref({
    email: '',
    password: ''
})

const form = ref({
    lapangan_id: '',
    tanggal: '',
    jam_mulai: '',
    jam_selesai: ''
})

const lapangans = ref([])
const peminjamans = ref([])
const editingId = ref(null)

const isLoggedIn = computed(() => !!token.value)

const isAdmin = computed(() => {
    return user.value?.role === 'admin'
})

const totalBooking = computed(() => {
    return peminjamans.value.length
})

const totalMenunggu = computed(() => {
    return peminjamans.value.filter(
        item => item.status === 'menunggu'
    ).length
})

const totalDisetujui = computed(() => {
    return peminjamans.value.filter(
        item => item.status === 'disetujui'
    ).length
})

const totalDitolak = computed(() => {
    return peminjamans.value.filter(
        item => item.status === 'ditolak'
    ).length
})

function saveAuth(data) {
    token.value = data.token
    user.value = data.user

    localStorage.setItem('token', data.token)
    localStorage.setItem(
        'user',
        JSON.stringify(data.user)
    )
}

function clearAuth() {
    token.value = null
    user.value = null

    localStorage.removeItem('token')
    localStorage.removeItem('user')
}

function resetMessages() {
    errorMessage.value = ''
    successMessage.value = ''
}

function resetForm() {
    form.value = {
        lapangan_id: '',
        tanggal: '',
        jam_mulai: '',
        jam_selesai: ''
    }

    editingId.value = null
}

function openCreateModal() {
    resetMessages()
    resetForm()
    showModal.value = true
}

function closeModal() {
    showModal.value = false
    resetForm()
}

function getErrorMessage(data) {
    if (data.message) {
        return data.message
    }

    if (data.errors) {
        return Object.values(data.errors)
            .flat()
            .join(' ')
    }

    return 'Terjadi kesalahan.'
}

async function login() {
    resetMessages()
    loading.value = true

    try {
        const response = await fetch(`${API}/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json'
            },
            body: JSON.stringify(loginForm.value)
        })

        const data = await response.json()

        if (!response.ok) {
            throw new Error(getErrorMessage(data))
        }

        saveAuth(data)

        loginForm.value = {
            email: '',
            password: ''
        }

        await loadData()

        activePage.value = 'dashboard'
    } catch (error) {
        errorMessage.value = error.message
    } finally {
        loading.value = false
    }
}

function openLogoutModal() {
    showLogoutModal.value = true
}

function closeLogoutModal() {
    showLogoutModal.value = false
}

async function logout() {
    loading.value = true

    try {
        await fetch(`${API}/logout`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token.value}`
            }
        })
    } catch (error) {
        console.error(error)
    }

    clearAuth()
    showLogoutModal.value = false
    peminjamans.value = []
    lapangans.value = []
    activePage.value = 'dashboard'
    loading.value = false
}

async function checkAuth() {
    if (!token.value) {
        return
    }

    try {
        const response = await fetch(`${API}/me`, {
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token.value}`
            }
        })

        if (!response.ok) {
            clearAuth()
            return
        }

        const data = await response.json()

        user.value = data.user

        localStorage.setItem(
            'user',
            JSON.stringify(data.user)
        )

        await loadData()
    } catch (error) {
        clearAuth()
    }
}

async function loadLapangan() {
    const response = await fetch(`${API}/lapangan`, {
        headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token.value}`
        }
    })

    const data = await response.json()

    if (!response.ok) {
        throw new Error(getErrorMessage(data))
    }

    lapangans.value = data.data
}

async function loadPeminjaman() {
    const response = await fetch(`${API}/peminjaman`, {
        headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token.value}`
        }
    })

    const data = await response.json()

    if (!response.ok) {
        throw new Error(getErrorMessage(data))
    }

    peminjamans.value = data.data
}

async function loadData() {
    if (!token.value) {
        return
    }

    loadingData.value = true
    resetMessages()

    try {
        await Promise.all([
            loadLapangan(),
            loadPeminjaman()
        ])
    } catch (error) {
        errorMessage.value = error.message
    } finally {
        loadingData.value = false
    }
}

async function savePeminjaman() {
    resetMessages()

    if (
        !form.value.lapangan_id ||
        !form.value.tanggal ||
        !form.value.jam_mulai ||
        !form.value.jam_selesai
    ) {
        errorMessage.value =
            'Semua data peminjaman wajib diisi.'

        return
    }

    loading.value = true

    const url = editingId.value
        ? `${API}/peminjaman/${editingId.value}`
        : `${API}/peminjaman`

    const method = editingId.value
        ? 'PUT'
        : 'POST'

    try {
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                Authorization: `Bearer ${token.value}`
            },
            body: JSON.stringify(form.value)
        })

        const data = await response.json()

        if (!response.ok) {
            throw new Error(getErrorMessage(data))
        }

        closeModal()

        successMessage.value = data.message

        await loadPeminjaman()
    } catch (error) {
        errorMessage.value = error.message
    } finally {
        loading.value = false
    }
}

function editPeminjaman(item) {
    resetMessages()

    if (
        !isAdmin.value &&
        item.status !== 'menunggu'
    ) {
        errorMessage.value =
            'Peminjaman yang sudah diproses tidak dapat diedit.'

        return
    }

    editingId.value = item.id

    form.value = {
        lapangan_id: item.lapangan_id,
        tanggal: item.tanggal,
        jam_mulai: item.jam_mulai.substring(0, 5),
        jam_selesai: item.jam_selesai.substring(0, 5)
    }

    showModal.value = true
}

async function updateStatus(item, status) {
    resetMessages()
    loading.value = true

    try {
        const response = await fetch(
            `${API}/peminjaman/${item.id}/status`,
            {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    Authorization: `Bearer ${token.value}`
                },
                body: JSON.stringify({ status })
            }
        )

        const data = await response.json()

        if (!response.ok) {
            throw new Error(getErrorMessage(data))
        }

        successMessage.value = data.message

        await loadPeminjaman()
    } catch (error) {
        errorMessage.value = error.message
    } finally {
        loading.value = false
    }
}

function formatTanggal(tanggal) {
    if (!tanggal) return '-'

    const date = new Date(`${tanggal}T00:00:00`)

    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    })
}

function formatRupiah(value) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
    }).format(value)
}

function statusLabel(status) {
    const labels = {
        menunggu: 'Menunggu',
        disetujui: 'Disetujui',
        ditolak: 'Ditolak'
    }

    return labels[status] || status
}

function setPage(page) {
    activePage.value = page
    resetMessages()

    if (page === 'peminjaman') {
        loadPeminjaman()
    }

    if (page === 'lapangan') {
        loadLapangan()
    }
}

function getInitials(name) {
    if (!name) return 'U'

    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .substring(0, 2)
        .toUpperCase()
}

onMounted(() => {
    checkAuth()
})
</script>

<template>
    <!-- ================= LOGIN ================= -->
    <div
        v-if="!isLoggedIn"
        class="login-screen"
    >
        <div class="login-decoration decoration-one"></div>
        <div class="login-decoration decoration-two"></div>

        <div class="login-container">

            <div class="login-brand">
                <div class="brand-mark">
                    <span>✦</span>
                </div>

                <div>
                    <div class="brand-name">
                        PADEL<span>PRO</span>
                    </div>

                    <div class="brand-caption">
                        COURT MANAGEMENT SYSTEM
                    </div>
                </div>
            </div>

            <div class="login-card">

                <div class="login-heading">
                    <div class="eyebrow">
                        WELCOME BACK
                    </div>

                    <h1>
                        Sign in to your account
                    </h1>

                    <p>
                        Kelola peminjaman lapangan
                        padel dengan mudah.
                    </p>
                </div>

                <div
                    v-if="errorMessage"
                    class="alert alert-error"
                >
                    <span class="alert-icon">!</span>
                    <span>{{ errorMessage }}</span>
                </div>

                <form
                    class="login-form"
                    @submit.prevent="login"
                >

                    <div class="input-group">
                        <label>Email</label>

                        <div class="input-wrapper">
                            <span class="input-icon">
                                @
                            </span>

                            <input
                                v-model="loginForm.email"
                                type="email"
                                placeholder="nama@email.com"
                                autocomplete="email"
                            >
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Password</label>

                        <div class="input-wrapper">
                            <span class="input-icon">
                                ●
                            </span>

                            <input
                                v-model="loginForm.password"
                                type="password"
                                placeholder="Masukkan password"
                                autocomplete="current-password"
                            >
                        </div>
                    </div>

                    <button
                        class="btn btn-primary login-button"
                        type="submit"
                        :disabled="loading"
                    >
                        <span>
                            {{
                                loading
                                    ? 'Signing in...'
                                    : 'Sign In'
                            }}
                        </span>

                        <span v-if="!loading">
                            →
                        </span>
                    </button>

                </form>

                <div class="demo-box">
                    <div class="demo-title">
                        DEMO ACCOUNT
                    </div>

                    <div class="demo-row">
                        <span>Admin</span>
                        <code>admin@padel.test</code>
                    </div>

                    <div class="demo-row">
                        <span>User</span>
                        <code>user@padel.test</code>
                    </div>

                    <div class="demo-password">
                        Password: <strong>password</strong>
                    </div>
                </div>

            </div>

            <div class="login-footer">
                © 2026 PadelPro · Sistem Informasi
                Peminjaman Lapangan
            </div>

        </div>
    </div>

    <!-- ================= APP ================= -->
    <div
        v-else
        class="app-layout"
    >

        <!-- SIDEBAR -->
        <aside class="sidebar">

            <div class="sidebar-brand">
                <div class="sidebar-logo">
                    ✦
                </div>

                <div>
                    <div class="sidebar-name">
                        PADEL<span>PRO</span>
                    </div>

                    <div class="sidebar-subtitle">
                        COURT MANAGEMENT
                    </div>
                </div>
            </div>

            <div class="sidebar-section">
                MAIN MENU
            </div>

            <nav class="sidebar-nav">

                <button
                    class="nav-item"
                    :class="{
                        active:
                            activePage === 'dashboard'
                    }"
                    @click="setPage('dashboard')"
                >
                    <span class="nav-icon">⌂</span>
                    <span>Dashboard</span>
                </button>

                <button
                    class="nav-item"
                    :class="{
                        active:
                            activePage === 'peminjaman'
                    }"
                    @click="setPage('peminjaman')"
                >
                    <span class="nav-icon">▣</span>
                    <span>Peminjaman</span>

                    <span
                        v-if="totalMenunggu > 0"
                        class="nav-badge"
                    >
                        {{ totalMenunggu }}
                    </span>
                </button>

                <button
                    class="nav-item"
                    :class="{
                        active:
                            activePage === 'lapangan'
                    }"
                    @click="setPage('lapangan')"
                >
                    <span class="nav-icon">⌑</span>
                    <span>Lapangan</span>
                </button>

            </nav>

            <div class="sidebar-section sidebar-bottom-title">
                ACCOUNT
            </div>

            <nav class="sidebar-nav">

                <button
                    class="nav-item"
                    @click="openLogoutModal"
                >
                    <span class="nav-icon logout-icon">
                        ↪
                    </span>

                    <span>Logout</span>
                </button>

            </nav>

            <div class="sidebar-user">

                <div class="avatar">
                    {{ getInitials(user.name) }}
                </div>

                <div class="sidebar-user-info">
                    <strong>
                        {{ user.name }}
                    </strong>

                    <span>
                        {{ isAdmin ? 'Administrator' : 'Member' }}
                    </span>
                </div>

                <div class="online-dot"></div>

            </div>

        </aside>

        <!-- MAIN -->
        <main class="main-content">

            <!-- TOPBAR -->
            <header class="topbar">

                <div class="mobile-brand">
                    <div class="sidebar-logo">
                        ✦
                    </div>

                    <strong>
                        PADEL<span>PRO</span>
                    </strong>
                </div>

                <div class="breadcrumb">
                    <span>Workspace</span>
                    <span>/</span>
                    <strong>
                        {{
                            activePage === 'dashboard'
                                ? 'Dashboard'
                                : activePage === 'peminjaman'
                                    ? 'Peminjaman'
                                    : 'Lapangan'
                        }}
                    </strong>
                </div>

                <div class="topbar-right">

                    <div class="date-display">
                        <span>●</span>
                        Sistem Online
                    </div>

                    <div class="topbar-profile">

                        <div class="avatar avatar-small">
                            {{ getInitials(user.name) }}
                        </div>

                        <div>
                            <strong>
                                {{ user.name }}
                            </strong>

                            <span>
                                {{ user.role }}
                            </span>
                        </div>

                    </div>

                </div>

            </header>

            <div class="content-wrapper">

                <!-- GLOBAL ALERT -->
                <div
                    v-if="successMessage"
                    class="alert alert-success global-alert"
                >
                    <span class="alert-icon">✓</span>
                    <span>{{ successMessage }}</span>

                    <button
                        @click="successMessage = ''"
                    >
                        ×
                    </button>
                </div>

                <div
                    v-if="errorMessage"
                    class="alert alert-error global-alert"
                >
                    <span class="alert-icon">!</span>
                    <span>{{ errorMessage }}</span>

                    <button
                        @click="errorMessage = ''"
                    >
                        ×
                    </button>
                </div>

                <!-- ================= DASHBOARD ================= -->
                <template
                    v-if="activePage === 'dashboard'"
                >

                    <section class="welcome-section">

                        <div>
                            <div class="eyebrow">
                                DASHBOARD
                            </div>

                            <h1>
                                Good morning,
                                <span>
                                    {{ user.name.split(' ')[0] }}
                                </span>
                                👋
                            </h1>

                            <p>
                                Pantau aktivitas peminjaman
                                lapangan kamu hari ini.
                            </p>
                        </div>

                        <button
                            class="btn btn-primary"
                            @click="openCreateModal"
                        >
                            <span>＋</span>
                            Booking Lapangan
                        </button>

                    </section>

                    <!-- STATISTICS -->
                    <section class="stats-grid">

                        <div class="stat-card">

                            <div class="stat-icon stat-blue">
                                ▣
                            </div>

                            <div class="stat-content">
                                <span>Total Booking</span>
                                <strong>
                                    {{ totalBooking }}
                                </strong>
                                <small>
                                    Semua peminjaman
                                </small>
                            </div>

                        </div>

                        <div class="stat-card">

                            <div class="stat-icon stat-orange">
                                ◷
                            </div>

                            <div class="stat-content">
                                <span>Menunggu</span>
                                <strong>
                                    {{ totalMenunggu }}
                                </strong>
                                <small>
                                    Perlu diproses
                                </small>
                            </div>

                        </div>

                        <div class="stat-card">

                            <div class="stat-icon stat-green">
                                ✓
                            </div>

                            <div class="stat-content">
                                <span>Disetujui</span>
                                <strong>
                                    {{ totalDisetujui }}
                                </strong>
                                <small>
                                    Booking aktif
                                </small>
                            </div>

                        </div>

                        <div class="stat-card">

                            <div class="stat-icon stat-red">
                                ×
                            </div>

                            <div class="stat-content">
                                <span>Ditolak</span>
                                <strong>
                                    {{ totalDitolak }}
                                </strong>
                                <small>
                                    Booking ditolak
                                </small>
                            </div>

                        </div>

                    </section>

                    <!-- COURTS -->
                    <section class="section-block">

                        <div class="section-heading">

                            <div>
                                <div class="eyebrow">
                                    OUR COURTS
                                </div>

                                <h2>
                                    Lapangan Tersedia
                                </h2>
                            </div>

                            <button
                                class="text-button"
                                @click="setPage('lapangan')"
                            >
                                Lihat semua →
                            </button>

                        </div>

                        <div class="court-grid">

                            <div
                                v-for="(
                                    lapangan,
                                    index
                                ) in lapangans"
                                :key="lapangan.id"
                                class="court-card"
                            >

                                <div
                                    class="court-image"
                                    :class="`court-${(index % 3) + 1}`"
                                >

                                    <div class="court-pattern">
                                        ⌁
                                    </div>

                                    <span class="court-status">
                                        ● Available
                                    </span>

                                    <div class="court-number">
                                        0{{ index + 1 }}
                                    </div>

                                </div>

                                <div class="court-info">

                                    <div>
                                        <h3>
                                            {{
                                                lapangan.nama_lapangan
                                            }}
                                        </h3>

                                        <p>
                                            {{
                                                lapangan.tipe
                                            }}
                                            Court
                                        </p>
                                    </div>

                                    <div class="court-price">
                                        <span>
                                            Mulai dari
                                        </span>

                                        <strong>
                                            {{
                                                formatRupiah(
                                                    lapangan.harga_per_jam
                                                )
                                            }}
                                        </strong>

                                        <small>
                                            / jam
                                        </small>
                                    </div>

                                </div>

                            </div>

                        </div>

                    </section>

                    <!-- RECENT BOOKINGS -->
                    <section class="section-block">

                        <div class="section-heading">

                            <div>
                                <div class="eyebrow">
                                    ACTIVITY
                                </div>

                                <h2>
                                    Peminjaman Terbaru
                                </h2>
                            </div>

                            <button
                                class="text-button"
                                @click="setPage('peminjaman')"
                            >
                                Lihat semua →
                            </button>

                        </div>

                        <div class="table-card">

                            <div
                                v-if="loadingData"
                                class="loading-state"
                            >
                                <div class="spinner"></div>
                                Memuat data...
                            </div>

                            <div
                                v-else-if="
                                    peminjamans.length === 0
                                "
                                class="empty-state"
                            >
                                <div class="empty-icon">
                                    ▣
                                </div>

                                <h3>
                                    Belum ada peminjaman
                                </h3>

                                <p>
                                    Buat booking pertama kamu
                                    sekarang.
                                </p>

                                <button
                                    class="btn btn-primary"
                                    @click="
                                        openCreateModal
                                    "
                                >
                                    Booking Sekarang
                                </button>
                            </div>

                            <table
                                v-else
                                class="data-table"
                            >

                                <thead>
                                    <tr>
                                        <th>Lapangan</th>
                                        <th>Tanggal</th>
                                        <th>Waktu</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <tr
                                        v-for="item in peminjamans.slice(
                                            0,
                                            5
                                        )"
                                        :key="item.id"
                                    >

                                        <td>
                                            <div class="table-court">
                                                <div
                                                    class="mini-court"
                                                >
                                                    ✦
                                                </div>

                                                <div>
                                                    <strong>
                                                        {{
                                                            item
                                                                .lapangan
                                                                ?.nama_lapangan
                                                        }}
                                                    </strong>

                                                    <span>
                                                        {{
                                                            item
                                                                .lapangan
                                                                ?.tipe
                                                        }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            {{
                                                formatTanggal(
                                                    item.tanggal
                                                )
                                            }}
                                        </td>

                                        <td>
                                            <strong>
                                                {{
                                                    item.jam_mulai.substring(
                                                        0,
                                                        5
                                                    )
                                                }}
                                                -
                                                {{
                                                    item.jam_selesai.substring(
                                                        0,
                                                        5
                                                    )
                                                }}
                                            </strong>
                                        </td>

                                        <td>
                                            <span
                                                class="status-badge"
                                                :class="
                                                    `status-${item.status}`
                                                "
                                            >
                                                <span>
                                                    ●
                                                </span>

                                                {{
                                                    statusLabel(
                                                        item.status
                                                    )
                                                }}
                                            </span>
                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </section>

                </template>

                <!-- ================= PEMINJAMAN ================= -->
                <template
                    v-if="activePage === 'peminjaman'"
                >

                    <section class="page-header">

                        <div>
                            <div class="eyebrow">
                                BOOKING MANAGEMENT
                            </div>

                            <h1>
                                Peminjaman
                            </h1>

                            <p>
                                {{
                                    isAdmin
                                        ? 'Kelola seluruh peminjaman lapangan.'
                                        : 'Kelola peminjaman lapangan kamu.'
                                }}
                            </p>
                        </div>

                        <button
                            class="btn btn-primary"
                            @click="openCreateModal"
                        >
                            ＋ Booking Baru
                        </button>

                    </section>

                    <section class="table-card">

                        <div class="table-toolbar">

                            <div>
                                <h3>
                                    Daftar Peminjaman
                                </h3>

                                <span>
                                    {{ peminjamans.length }}
                                    data ditemukan
                                </span>
                            </div>

                            <button
                                class="refresh-button"
                                @click="loadPeminjaman"
                            >
                                ↻ Refresh
                            </button>

                        </div>

                        <div
                            v-if="loadingData"
                            class="loading-state"
                        >
                            <div class="spinner"></div>
                            Memuat data...
                        </div>

                        <div
                            v-else-if="
                                peminjamans.length === 0
                            "
                            class="empty-state"
                        >
                            <div class="empty-icon">
                                ▣
                            </div>

                            <h3>
                                Belum ada peminjaman
                            </h3>

                            <p>
                                Belum ada data booking
                                lapangan.
                            </p>
                        </div>

                        <div
                            v-else
                            class="table-scroll"
                        >

                            <table class="data-table">

                                <thead>

                                    <tr>

                                        <th>
                                            #
                                        </th>

                                        <th
                                            v-if="isAdmin"
                                        >
                                            Peminjam
                                        </th>

                                        <th>
                                            Lapangan
                                        </th>

                                        <th>
                                            Tanggal
                                        </th>

                                        <th>
                                            Waktu
                                        </th>

                                        <th>
                                            Status
                                        </th>

                                        <th>
                                            Aksi
                                        </th>

                                    </tr>

                                </thead>

                                <tbody>

                                    <tr
                                        v-for="(
                                            item,
                                            index
                                        ) in peminjamans"
                                        :key="item.id"
                                    >

                                        <td>
                                            <span
                                                class="row-number"
                                            >
                                                {{
                                                    String(
                                                        index + 1
                                                    ).padStart(
                                                        2,
                                                        '0'
                                                    )
                                                }}
                                            </span>
                                        </td>

                                        <td
                                            v-if="isAdmin"
                                        >
                                            <div
                                                class="user-cell"
                                            >
                                                <div
                                                    class="mini-avatar"
                                                >
                                                    {{
                                                        getInitials(
                                                            item
                                                                .user
                                                                ?.name
                                                        )
                                                    }}
                                                </div>

                                                <div>
                                                    <strong>
                                                        {{
                                                            item
                                                                .user
                                                                ?.name
                                                        }}
                                                    </strong>

                                                    <span>
                                                        {{
                                                            item
                                                                .user
                                                                ?.email
                                                        }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <div
                                                class="table-court"
                                            >
                                                <div
                                                    class="mini-court"
                                                >
                                                    ✦
                                                </div>

                                                <div>
                                                    <strong>
                                                        {{
                                                            item
                                                                .lapangan
                                                                ?.nama_lapangan
                                                        }}
                                                    </strong>

                                                    <span>
                                                        {{
                                                            item
                                                                .lapangan
                                                                ?.tipe
                                                        }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            {{
                                                formatTanggal(
                                                    item.tanggal
                                                )
                                            }}
                                        </td>

                                        <td>
                                            <div
                                                class="time-cell"
                                            >
                                                {{
                                                    item.jam_mulai.substring(
                                                        0,
                                                        5
                                                    )
                                                }}
                                                <span>
                                                    →
                                                </span>
                                                {{
                                                    item.jam_selesai.substring(
                                                        0,
                                                        5
                                                    )
                                                }}
                                            </div>
                                        </td>

                                        <td>
                                            <span
                                                class="status-badge"
                                                :class="
                                                    `status-${item.status}`
                                                "
                                            >
                                                <span>
                                                    ●
                                                </span>

                                                {{
                                                    statusLabel(
                                                        item.status
                                                    )
                                                }}
                                            </span>
                                        </td>

                                        <td>
                                            <div
                                                class="action-group"
                                            >

                                                <button
                                                    v-if="
                                                        item.status ===
                                                            'menunggu' ||
                                                        isAdmin
                                                    "
                                                    class="icon-button edit-button"
                                                    title="Edit"
                                                    @click="
                                                        editPeminjaman(
                                                            item
                                                        )
                                                    "
                                                >
                                                    ✎
                                                </button>

                                                <template
                                                    v-if="
                                                        isAdmin
                                                    "
                                                >

                                                    <button
                                                        v-if="
                                                            item.status !==
                                                            'disetujui'
                                                        "
                                                        class="icon-button approve-button"
                                                        title="Setujui"
                                                        @click="
                                                            updateStatus(
                                                                item,
                                                                'disetujui'
                                                            )
                                                        "
                                                    >
                                                        ✓
                                                    </button>

                                                    <button
                                                        v-if="
                                                            item.status !==
                                                            'ditolak'
                                                        "
                                                        class="icon-button reject-button"
                                                        title="Tolak"
                                                        @click="
                                                            updateStatus(
                                                                item,
                                                                'ditolak'
                                                            )
                                                        "
                                                    >
                                                        ×
                                                    </button>

                                                </template>

                                            </div>
                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </section>

                </template>

                <!-- ================= LAPANGAN ================= -->
                <template
                    v-if="activePage === 'lapangan'"
                >

                    <section class="page-header">

                        <div>
                            <div class="eyebrow">
                                COURT DIRECTORY
                            </div>

                            <h1>
                                Lapangan
                            </h1>

                            <p>
                                Pilih lapangan terbaik untuk
                                permainan kamu.
                            </p>
                        </div>

                    </section>

                    <div class="court-grid court-page-grid">

                        <div
                            v-for="(
                                lapangan,
                                index
                            ) in lapangans"
                            :key="lapangan.id"
                            class="court-card large-court"
                        >

                            <div
                                class="court-image"
                                :class="
                                    `court-${(index % 3) + 1}`
                                "
                            >

                                <div class="court-pattern">
                                    ⌁
                                </div>

                                <span class="court-status">
                                    ● Available
                                </span>

                                <div class="court-number">
                                    0{{ index + 1 }}
                                </div>

                            </div>

                            <div class="court-info">

                                <div>
                                    <h3>
                                        {{
                                            lapangan.nama_lapangan
                                        }}
                                    </h3>

                                    <p>
                                        {{ lapangan.tipe }}
                                        Court
                                    </p>
                                </div>

                                <div class="court-price">
                                    <span>
                                        Harga per jam
                                    </span>

                                    <strong>
                                        {{
                                            formatRupiah(
                                                lapangan.harga_per_jam
                                            )
                                        }}
                                    </strong>
                                </div>

                            </div>

                            <button
                                class="btn btn-primary full-button"
                                @click="openCreateModal"
                            >
                                Booking Lapangan
                                →
                            </button>

                        </div>

                    </div>

                </template>

            </div>

        </main>

    </div>

    <!-- ================= BOOKING MODAL ================= -->
    <Transition name="modal">

        <div
            v-if="showModal"
            class="modal-overlay"
            @click.self="closeModal"
        >

            <div class="modal-card">

                <div class="modal-header">

                    <div>
                        <div class="eyebrow">
                            {{
                                editingId
                                    ? 'EDIT BOOKING'
                                    : 'NEW BOOKING'
                            }}
                        </div>

                        <h2>
                            {{
                                editingId
                                    ? 'Edit Peminjaman'
                                    : 'Booking Lapangan'
                            }}
                        </h2>

                        <p>
                            Isi detail jadwal peminjaman.
                        </p>
                    </div>

                    <button
                        class="modal-close"
                        @click="closeModal"
                    >
                        ×
                    </button>

                </div>

                <form
                    class="booking-form"
                    @submit.prevent="savePeminjaman"
                >

                    <div class="form-field">

                        <label>
                            Lapangan
                        </label>

                        <select
                            v-model="form.lapangan_id"
                        >

                            <option value="">
                                Pilih lapangan
                            </option>

                            <option
                                v-for="lapangan in lapangans"
                                :key="lapangan.id"
                                :value="lapangan.id"
                            >
                                {{
                                    lapangan.nama_lapangan
                                }}
                                ·
                                {{
                                    lapangan.tipe
                                }}
                                ·
                                {{
                                    formatRupiah(
                                        lapangan.harga_per_jam
                                    )
                                }}/jam
                            </option>

                        </select>

                    </div>

                    <div class="form-field">

                        <label>
                            Tanggal
                        </label>

                        <input
                            v-model="form.tanggal"
                            type="date"
                        >

                    </div>

                    <div class="form-row">

                        <div class="form-field">

                            <label>
                                Jam Mulai
                            </label>

                            <input
                                v-model="form.jam_mulai"
                                type="time"
                            >

                        </div>

                        <div class="form-field">

                            <label>
                                Jam Selesai
                            </label>

                            <input
                                v-model="form.jam_selesai"
                                type="time"
                            >

                        </div>

                    </div>

                    <div class="booking-note">
                        <span>ⓘ</span>

                        <p>
                            Pastikan waktu yang dipilih
                            belum digunakan oleh booking
                            lain.
                        </p>
                    </div>

                    <div
                        v-if="errorMessage"
                        class="alert alert-error"
                    >
                        <span class="alert-icon">!</span>
                        <span>
                            {{ errorMessage }}
                        </span>
                    </div>

                    <div class="modal-actions">

                        <button
                            type="button"
                            class="btn btn-secondary"
                            @click="closeModal"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            class="btn btn-primary"
                            :disabled="loading"
                        >
                            {{
                                loading
                                    ? 'Menyimpan...'
                                    : editingId
                                        ? 'Simpan Perubahan'
                                        : 'Konfirmasi Booking'
                            }}
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </Transition>

    <!-- ================= LOGOUT MODAL ================= -->
    <Transition name="modal">

        <div
            v-if="showLogoutModal"
            class="modal-overlay"
            @click.self="closeLogoutModal"
        >

            <div class="confirm-card">

                <div class="confirm-icon">
                    ↪
                </div>

                <h2>
                    Keluar dari akun?
                </h2>

                <p>
                    Kamu akan keluar dari dashboard
                    PadelPro.
                </p>

                <div class="confirm-actions">

                    <button
                        class="btn btn-secondary"
                        @click="closeLogoutModal"
                    >
                        Batal
                    </button>

                    <button
                        class="btn btn-danger"
                        @click="logout"
                    >
                        Logout
                    </button>

                </div>

            </div>

        </div>

    </Transition>
</template>

<style>
/* =========================================================
   GLOBAL
========================================================= */

@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap');

:root {
    --bg: #f4f6f5;
    --surface: #ffffff;
    --surface-soft: #f8faf9;
    --text: #15201b;
    --muted: #7b8580;
    --border: #e5e9e6;

    --primary: #16251e;
    --primary-light: #23372e;

    --accent: #c9f31d;
    --accent-dark: #a8d600;

    --green: #16a36a;
    --green-soft: #e8f8f0;

    --orange: #e99b35;
    --orange-soft: #fff4e4;

    --red: #e45858;
    --red-soft: #fff0f0;

    --blue: #4b7bec;
    --blue-soft: #eef3ff;

    --shadow:
        0 10px 30px rgba(18, 32, 25, 0.06);

    --shadow-lg:
        0 20px 60px rgba(18, 32, 25, 0.14);

    --radius: 16px;
}

* {
    box-sizing: border-box;
}

html,
body,
#app {
    margin: 0;
    min-height: 100%;
}

body {
    font-family:
        'DM Sans',
        Arial,
        sans-serif;

    background: var(--bg);
    color: var(--text);
}

button,
input,
select {
    font-family: inherit;
}

button {
    border: 0;
}

button:focus-visible,
input:focus-visible,
select:focus-visible {
    outline: 3px solid rgba(201, 243, 29, 0.3);
    outline-offset: 2px;
}

/* =========================================================
   LOGIN
========================================================= */

.login-screen {
    min-height: 100vh;
    position: relative;
    overflow: hidden;

    display: flex;
    align-items: center;
    justify-content: center;

    background:
        radial-gradient(
            circle at 15% 15%,
            #294235 0,
            transparent 28%
        ),
        radial-gradient(
            circle at 85% 85%,
            #253c31 0,
            transparent 28%
        ),
        #101b16;

    padding: 30px;
}

.login-decoration {
    position: absolute;
    border: 1px solid rgba(201, 243, 29, 0.1);
    border-radius: 50%;
}

.decoration-one {
    width: 550px;
    height: 550px;
    left: -350px;
    top: -200px;
}

.decoration-two {
    width: 650px;
    height: 650px;
    right: -400px;
    bottom: -300px;
}

.login-container {
    width: 100%;
    max-width: 450px;
    position: relative;
    z-index: 2;
}

.login-brand {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 13px;
    color: white;
    margin-bottom: 25px;
}

.brand-mark {
    width: 44px;
    height: 44px;
    display: grid;
    place-items: center;

    background: var(--accent);
    color: var(--primary);

    border-radius: 12px;

    font-size: 25px;
    font-weight: 700;

    box-shadow:
        0 10px 30px
        rgba(201, 243, 29, 0.15);
}

.brand-name {
    font-family:
        'Space Grotesk',
        sans-serif;

    font-size: 22px;
    font-weight: 700;
    letter-spacing: -0.5px;
}

.brand-name span,
.sidebar-name span,
.mobile-brand strong span {
    color: var(--accent);
}

.brand-caption {
    color: #89968f;
    font-size: 8px;
    letter-spacing: 2px;
    margin-top: 2px;
}

.login-card {
    background: rgba(255, 255, 255, 0.98);

    border-radius: 24px;

    padding: 38px;

    box-shadow: var(--shadow-lg);
}

.login-heading {
    margin-bottom: 28px;
}

.eyebrow {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.8px;
    color: #8a958f;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.login-heading h1 {
    font-family:
        'Space Grotesk',
        sans-serif;

    font-size: 27px;
    line-height: 1.2;

    margin: 0 0 9px;

    letter-spacing: -1px;
}

.login-heading p {
    color: var(--muted);
    margin: 0;
    font-size: 14px;
    line-height: 1.6;
}

.login-form {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.input-group label,
.form-field label {
    display: block;

    font-size: 12px;
    font-weight: 700;

    margin-bottom: 8px;
}

.input-wrapper {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);

    color: #9ca6a1;

    font-size: 13px;
}

.input-wrapper input {
    padding-left: 40px;
}

input,
select {
    width: 100%;

    border: 1px solid var(--border);

    background: var(--surface-soft);

    border-radius: 10px;

    height: 46px;

    padding: 0 13px;

    color: var(--text);

    font-size: 13px;

    transition:
        border-color 0.2s,
        box-shadow 0.2s,
        background 0.2s;
}

input:focus,
select:focus {
    border-color: #b7c5bd;
    background: white;

    box-shadow:
        0 0 0 4px rgba(201, 243, 29, 0.08);

    outline: none;
}

.btn {
    height: 44px;

    border-radius: 10px;

    padding: 0 17px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    gap: 9px;

    font-size: 12px;
    font-weight: 700;

    cursor: pointer;

    transition:
        transform 0.2s,
        box-shadow 0.2s,
        background 0.2s,
        opacity 0.2s;
}

.btn:hover {
    transform: translateY(-1px);
}

.btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
    transform: none;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-light);

    box-shadow:
        0 8px 20px
        rgba(21, 37, 30, 0.15);
}

.login-button {
    height: 50px;
    margin-top: 3px;
    justify-content: space-between;
    padding: 0 18px;
}

.demo-box {
    margin-top: 25px;

    border: 1px dashed #d8dfda;

    border-radius: 12px;

    padding: 14px 16px;

    background: #fbfcfb;
}

.demo-title {
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 1.5px;
    color: #8a958f;
    margin-bottom: 10px;
}

.demo-row {
    display: flex;
    align-items: center;
    justify-content: space-between;

    font-size: 11px;

    padding: 4px 0;
}

.demo-row span {
    color: var(--muted);
}

.demo-row code {
    font-family: monospace;
    color: var(--text);
    font-size: 10px;
}

.demo-password {
    border-top: 1px solid #edf0ee;
    margin-top: 7px;
    padding-top: 9px;

    color: var(--muted);

    font-size: 10px;
}

.demo-password strong {
    color: var(--text);
}

.login-footer {
    text-align: center;
    color: #718078;
    font-size: 10px;
    margin-top: 20px;
}

/* =========================================================
   ALERT
========================================================= */

.alert {
    border-radius: 10px;

    padding: 12px 14px;

    display: flex;
    align-items: center;
    gap: 10px;

    font-size: 12px;

    margin-bottom: 17px;
}

.alert-error {
    background: var(--red-soft);
    color: #b13939;
    border: 1px solid #f6d8d8;
}

.alert-success {
    background: var(--green-soft);
    color: #14754d;
    border: 1px solid #d3efdf;
}

.alert-icon {
    width: 20px;
    height: 20px;

    display: grid;
    place-items: center;

    border-radius: 50%;

    font-weight: 800;
}

.alert-error .alert-icon {
    background: #f6d6d6;
}

.alert-success .alert-icon {
    background: #ccebd9;
}

.global-alert {
    justify-content: flex-start;
}

.global-alert button {
    margin-left: auto;
    background: transparent;
    color: inherit;
    font-size: 20px;
    cursor: pointer;
}

/* =========================================================
   APP LAYOUT
========================================================= */

.app-layout {
    min-height: 100vh;
    display: flex;
    background: var(--bg);
}

.sidebar {
    width: 245px;

    background: var(--primary);
    color: white;

    min-height: 100vh;

    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;

    display: flex;
    flex-direction: column;

    padding: 25px 15px;

    z-index: 20;
}

.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 11px;

    padding: 0 10px;

    margin-bottom: 42px;
}

.sidebar-logo {
    width: 38px;
    height: 38px;

    display: grid;
    place-items: center;

    border-radius: 10px;

    background: var(--accent);
    color: var(--primary);

    font-weight: 800;
}

.sidebar-name {
    font-family:
        'Space Grotesk',
        sans-serif;

    font-size: 17px;
    font-weight: 700;
}

.sidebar-subtitle {
    font-size: 7px;
    letter-spacing: 1.4px;
    color: #718078;
    margin-top: 2px;
}

.sidebar-section {
    padding: 0 13px;

    color: #627169;

    font-size: 8px;
    font-weight: 800;

    letter-spacing: 1.7px;

    margin-bottom: 9px;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.nav-item {
    position: relative;

    width: 100%;

    border-radius: 9px;

    padding: 11px 13px;

    background: transparent;

    color: #aebbb4;

    display: flex;
    align-items: center;

    gap: 11px;

    text-align: left;

    cursor: pointer;

    font-size: 12px;
    font-weight: 500;

    transition:
        color 0.2s,
        background 0.2s;
}

.nav-item:hover {
    background: rgba(255, 255, 255, 0.05);
    color: white;
}

.nav-item.active {
    background: #263b31;
    color: white;
}

.nav-item.active::before {
    content: '';

    position: absolute;

    left: 0;
    top: 10px;
    bottom: 10px;

    width: 3px;

    border-radius: 0 3px 3px 0;

    background: var(--accent);
}

.nav-icon {
    width: 20px;
    text-align: center;
    font-size: 15px;
    color: #84948c;
}

.nav-item.active .nav-icon {
    color: var(--accent);
}

.nav-badge {
    margin-left: auto;

    min-width: 20px;
    height: 20px;

    display: grid;
    place-items: center;

    border-radius: 20px;

    background: var(--accent);
    color: var(--primary);

    font-size: 9px;
    font-weight: 800;
}

.sidebar-bottom-title {
    margin-top: auto;
}

.sidebar-user {
    margin-top: 20px;

    border-top: 1px solid rgba(255, 255, 255, 0.07);

    padding: 18px 8px 0;

    display: flex;
    align-items: center;

    gap: 9px;
}

.avatar {
    width: 36px;
    height: 36px;

    border-radius: 10px;

    display: grid;
    place-items: center;

    background:
        linear-gradient(
            135deg,
            #d6f878,
            #9ed100
        );

    color: var(--primary);

    font-size: 10px;
    font-weight: 800;
}

.avatar-small {
    width: 34px;
    height: 34px;
}

.sidebar-user-info {
    display: flex;
    flex-direction: column;

    min-width: 0;
}

.sidebar-user-info strong {
    font-size: 11px;

    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.sidebar-user-info span {
    color: #728078;
    font-size: 9px;
    margin-top: 2px;
}

.online-dot {
    width: 7px;
    height: 7px;

    border-radius: 50%;

    background: #45d695;

    margin-left: auto;

    box-shadow:
        0 0 0 3px rgba(69, 214, 149, 0.1);
}

/* =========================================================
   TOPBAR
========================================================= */

.main-content {
    margin-left: 245px;

    width: calc(100% - 245px);

    min-height: 100vh;
}

.topbar {
    height: 70px;

    background: white;

    border-bottom: 1px solid var(--border);

    padding: 0 35px;

    display: flex;
    align-items: center;
    justify-content: space-between;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 9px;

    color: #a0aaa5;

    font-size: 11px;
}

.breadcrumb strong {
    color: var(--text);
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 24px;
}

.date-display {
    color: #78837d;
    font-size: 10px;

    display: flex;
    align-items: center;
    gap: 6px;
}

.date-display span {
    color: #32b97d;
    font-size: 8px;
}

.topbar-profile {
    display: flex;
    align-items: center;
    gap: 9px;
}

.topbar-profile > div:last-child {
    display: flex;
    flex-direction: column;
}

.topbar-profile strong {
    font-size: 10px;
}

.topbar-profile span {
    font-size: 9px;
    color: var(--muted);
    margin-top: 2px;
}

.mobile-brand {
    display: none;
}

/* =========================================================
   CONTENT
========================================================= */

.content-wrapper {
    max-width: 1450px;

    padding: 36px 40px 60px;

    margin: 0 auto;
}

.welcome-section,
.page-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;

    margin-bottom: 28px;
}

.welcome-section h1,
.page-header h1 {
    font-family:
        'Space Grotesk',
        sans-serif;

    font-size: 30px;

    letter-spacing: -1.3px;

    margin: 0 0 6px;
}

.welcome-section h1 span {
    color: #879a91;
}

.welcome-section p,
.page-header p {
    color: var(--muted);
    font-size: 12px;
    margin: 0;
}

.stats-grid {
    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 14px;

    margin-bottom: 38px;
}

.stat-card {
    background: white;

    border: 1px solid var(--border);

    border-radius: var(--radius);

    padding: 19px;

    display: flex;
    align-items: center;

    gap: 14px;

    box-shadow: var(--shadow);
}

.stat-icon {
    width: 44px;
    height: 44px;

    display: grid;
    place-items: center;

    border-radius: 12px;

    font-size: 17px;
    font-weight: 700;
}

.stat-blue {
    background: var(--blue-soft);
    color: var(--blue);
}

.stat-orange {
    background: var(--orange-soft);
    color: var(--orange);
}

.stat-green {
    background: var(--green-soft);
    color: var(--green);
}

.stat-red {
    background: var(--red-soft);
    color: var(--red);
}

.stat-content {
    display: flex;
    flex-direction: column;
}

.stat-content span {
    font-size: 10px;
    color: var(--muted);
}

.stat-content strong {
    font-family:
        'Space Grotesk',
        sans-serif;

    font-size: 23px;

    margin: 2px 0;
}

.stat-content small {
    color: #a0aaa5;
    font-size: 8px;
}

.section-block {
    margin-bottom: 38px;
}

.section-heading {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;

    margin-bottom: 15px;
}

.section-heading h2 {
    font-family:
        'Space Grotesk',
        sans-serif;

    font-size: 18px;

    margin: 0;

    letter-spacing: -0.5px;
}

.text-button {
    background: transparent;

    color: #607069;

    font-size: 10px;
    font-weight: 700;

    cursor: pointer;
}

.text-button:hover {
    color: var(--primary);
}

/* =========================================================
   COURT CARDS
========================================================= */

.court-grid {
    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 15px;
}

.court-card {
    background: white;

    border: 1px solid var(--border);

    border-radius: var(--radius);

    overflow: hidden;

    box-shadow: var(--shadow);

    transition:
        transform 0.25s,
        box-shadow 0.25s;
}

.court-card:hover {
    transform: translateY(-3px);

    box-shadow:
        0 16px 35px
        rgba(18, 32, 25, 0.1);
}

.court-image {
    height: 135px;

    position: relative;

    overflow: hidden;

    background:
        linear-gradient(
            135deg,
            #20382d,
            #395d49
        );
}

.court-2 {
    background:
        linear-gradient(
            135deg,
            #31423d,
            #60746a
        );
}

.court-3 {
    background:
        linear-gradient(
            135deg,
            #29382f,
            #52725e
        );
}

.court-pattern {
    position: absolute;

    font-size: 110px;

    color: rgba(255, 255, 255, 0.04);

    right: 10px;
    bottom: -40px;

    transform: rotate(-20deg);
}

.court-status {
    position: absolute;

    top: 13px;
    left: 13px;

    background: rgba(13, 28, 21, 0.65);

    backdrop-filter: blur(8px);

    border: 1px solid rgba(255, 255, 255, 0.1);

    color: #d9f7a1;

    border-radius: 30px;

    padding: 6px 9px;

    font-size: 8px;
    font-weight: 700;
}

.court-number {
    position: absolute;

    right: 14px;
    bottom: 10px;

    color: rgba(255, 255, 255, 0.22);

    font-family:
        'Space Grotesk',
        sans-serif;

    font-size: 40px;
    font-weight: 700;
}

.court-info {
    padding: 16px;

    display: flex;
    align-items: flex-end;
    justify-content: space-between;
}

.court-info h3 {
    font-family:
        'Space Grotesk',
        sans-serif;

    font-size: 14px;

    margin: 0 0 4px;
}

.court-info p {
    margin: 0;

    color: var(--muted);

    font-size: 9px;
}

.court-price {
    text-align: right;

    display: flex;
    flex-direction: column;
}

.court-price span {
    color: #a0aaa5;
    font-size: 8px;
}

.court-price strong {
    color: var(--text);
    font-size: 11px;
    margin-top: 2px;
}

.court-price small {
    color: #a0aaa5;
    font-size: 8px;
}

.large-court .court-image {
    height: 190px;
}

.full-button {
    width: calc(100% - 32px);
    margin: 0 16px 16px;
}

/* =========================================================
   TABLE
========================================================= */

.table-card {
    background: white;

    border: 1px solid var(--border);

    border-radius: var(--radius);

    overflow: hidden;

    box-shadow: var(--shadow);
}

.table-toolbar {
    min-height: 65px;

    padding: 14px 19px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    border-bottom: 1px solid var(--border);
}

.table-toolbar h3 {
    font-family:
        'Space Grotesk',
        sans-serif;

    font-size: 13px;

    margin: 0 0 3px;
}

.table-toolbar span {
    color: var(--muted);
    font-size: 9px;
}

.refresh-button {
    background: var(--surface-soft);

    border: 1px solid var(--border);

    color: #67736d;

    border-radius: 8px;

    padding: 8px 11px;

    font-size: 10px;

    cursor: pointer;
}

.table-scroll {
    overflow-x: auto;
}

.data-table {
    width: 100%;

    border-collapse: collapse;

    font-size: 10px;
}

.data-table th {
    text-align: left;

    background: #fafbfa;

    color: #8a958f;

    font-size: 8px;

    font-weight: 800;

    letter-spacing: 0.7px;

    text-transform: uppercase;

    padding: 12px 18px;

    border-bottom: 1px solid var(--border);
}

.data-table td {
    padding: 13px 18px;

    border-bottom: 1px solid #eef1ef;

    color: #5c6862;
}

.data-table tbody tr:last-child td {
    border-bottom: 0;
}

.data-table tbody tr {
    transition: background 0.15s;
}

.data-table tbody tr:hover {
    background: #fcfdfc;
}

.table-court {
    display: flex;
    align-items: center;
    gap: 9px;
}

.table-court > div:last-child {
    display: flex;
    flex-direction: column;
}

.table-court strong {
    color: var(--text);
    font-size: 10px;
}

.table-court span {
    color: #9aa49f;
    font-size: 8px;
    margin-top: 2px;
}

.mini-court {
    width: 32px;
    height: 32px;

    display: grid;
    place-items: center;

    border-radius: 8px;

    background: var(--primary);

    color: var(--accent);

    font-size: 13px;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;

    border-radius: 30px;

    padding: 6px 9px;

    font-size: 8px;
    font-weight: 700;
}

.status-badge span {
    font-size: 7px;
}

.status-menunggu {
    color: #b26c13;
    background: var(--orange-soft);
}

.status-disetujui {
    color: #168253;
    background: var(--green-soft);
}

.status-ditolak {
    color: #bf4545;
    background: var(--red-soft);
}

.row-number {
    color: #9aa49f;
    font-family: monospace;
}

.time-cell {
    display: flex;
    gap: 7px;
    align-items: center;

    color: var(--text);
}

.time-cell span {
    color: #9ca7a1;
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 8px;
}

.user-cell > div:last-child {
    display: flex;
    flex-direction: column;
}

.user-cell strong {
    color: var(--text);
    font-size: 10px;
}

.user-cell span {
    color: #9ca7a1;
    font-size: 8px;
    margin-top: 2px;
}

.mini-avatar {
    width: 29px;
    height: 29px;

    border-radius: 8px;

    display: grid;
    place-items: center;

    background: #edf5e1;

    color: #63821c;

    font-size: 8px;
    font-weight: 800;
}

.action-group {
    display: flex;
    gap: 5px;
}

.icon-button {
    width: 29px;
    height: 29px;

    display: grid;
    place-items: center;

    border-radius: 7px;

    cursor: pointer;

    font-size: 11px;

    transition:
        transform 0.15s,
        opacity 0.15s;
}

.icon-button:hover {
    transform: translateY(-1px);
}

.edit-button {
    background: var(--blue-soft);
    color: var(--blue);
}

.approve-button {
    background: var(--green-soft);
    color: var(--green);
}

.reject-button {
    background: var(--red-soft);
    color: var(--red);
}

/* =========================================================
   EMPTY / LOADING
========================================================= */

.empty-state {
    min-height: 260px;

    display: flex;
    flex-direction: column;

    align-items: center;
    justify-content: center;

    text-align: center;

    padding: 30px;
}

.empty-icon {
    width: 50px;
    height: 50px;

    display: grid;
    place-items: center;

    border-radius: 14px;

    background: var(--surface-soft);

    color: #9aa49f;

    font-size: 20px;

    margin-bottom: 13px;
}

.empty-state h3 {
    font-family:
        'Space Grotesk',
        sans-serif;

    font-size: 15px;

    margin: 0 0 5px;
}

.empty-state p {
    color: var(--muted);
    font-size: 10px;
    margin: 0 0 15px;
}

.loading-state {
    min-height: 220px;

    display: flex;
    flex-direction: column;

    align-items: center;
    justify-content: center;

    gap: 12px;

    color: var(--muted);

    font-size: 11px;
}

.spinner {
    width: 28px;
    height: 28px;

    border: 3px solid #e6ebe8;

    border-top-color: var(--primary);

    border-radius: 50%;

    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* =========================================================
   MODAL
========================================================= */

.modal-overlay {
    position: fixed;

    inset: 0;

    z-index: 100;

    background:
        rgba(10, 20, 15, 0.55);

    backdrop-filter: blur(5px);

    display: flex;

    align-items: center;
    justify-content: center;

    padding: 20px;
}

.modal-card {
    width: 100%;
    max-width: 500px;

    background: white;

    border-radius: 20px;

    box-shadow: var(--shadow-lg);

    overflow: hidden;
}

.modal-header {
    padding: 25px 27px 20px;

    display: flex;
    justify-content: space-between;

    border-bottom: 1px solid var(--border);
}

.modal-header h2 {
    font-family:
        'Space Grotesk',
        sans-serif;

    margin: 0 0 5px;

    font-size: 21px;

    letter-spacing: -0.6px;
}

.modal-header p {
    margin: 0;

    color: var(--muted);

    font-size: 10px;
}

.modal-close {
    width: 30px;
    height: 30px;

    display: grid;
    place-items: center;

    border-radius: 8px;

    background: var(--surface-soft);

    color: #6d7872;

    cursor: pointer;

    font-size: 20px;
}

.booking-form {
    padding: 24px 27px 27px;
}

.form-field {
    margin-bottom: 16px;
}

.form-row {
    display: grid;

    grid-template-columns:
        1fr 1fr;

    gap: 13px;
}

.booking-note {
    display: flex;
    gap: 8px;

    background: #f5f8f5;

    border: 1px solid #e6ebe7;

    border-radius: 9px;

    padding: 10px 12px;

    margin-bottom: 17px;
}

.booking-note span {
    color: #809088;
    font-size: 12px;
}

.booking-note p {
    margin: 0;

    color: #7b8780;

    font-size: 9px;

    line-height: 1.5;
}

.modal-actions {
    display: flex;

    justify-content: flex-end;

    gap: 8px;

    padding-top: 3px;
}

.btn-secondary {
    background: #eef1ef;
    color: #59665f;
}

.btn-danger {
    background: var(--red);
    color: white;
}

.confirm-card {
    width: 100%;
    max-width: 360px;

    background: white;

    border-radius: 20px;

    padding: 30px;

    text-align: center;

    box-shadow: var(--shadow-lg);
}

.confirm-icon {
    width: 54px;
    height: 54px;

    display: grid;
    place-items: center;

    margin: 0 auto 17px;

    border-radius: 15px;

    background: var(--red-soft);

    color: var(--red);

    font-size: 23px;
}

.confirm-card h2 {
    font-family:
        'Space Grotesk',
        sans-serif;

    margin: 0 0 7px;

    font-size: 20px;
}

.confirm-card p {
    color: var(--muted);

    font-size: 11px;

    margin: 0 0 23px;
}

.confirm-actions {
    display: flex;

    gap: 8px;

    justify-content: center;
}

.confirm-actions .btn {
    min-width: 100px;
}

/* =========================================================
   TRANSITIONS
========================================================= */

.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active .modal-card,
.modal-enter-active .confirm-card {
    transition:
        transform 0.2s ease,
        opacity 0.2s ease;
}

.modal-enter-from .modal-card,
.modal-enter-from .confirm-card {
    transform: translateY(12px) scale(0.98);
    opacity: 0;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1100px) {
    .stats-grid {
        grid-template-columns:
            repeat(2, 1fr);
    }

    .court-grid {
        grid-template-columns:
            repeat(2, 1fr);
    }

    .date-display {
        display: none;
    }
}

@media (max-width: 800px) {
    .sidebar {
        width: 70px;
        padding: 20px 10px;
    }

    .sidebar-brand {
        justify-content: center;
        padding: 0;
    }

    .sidebar-brand > div:not(.sidebar-logo),
    .sidebar-section,
    .nav-item span:not(.nav-icon),
    .sidebar-user-info,
    .online-dot {
        display: none;
    }

    .nav-item {
        justify-content: center;
        padding: 12px;
    }

    .nav-item.active::before {
        display: none;
    }

    .nav-badge {
        position: absolute;
        top: 3px;
        right: 3px;
    }

    .sidebar-user {
        justify-content: center;
        padding-left: 0;
        padding-right: 0;
    }

    .main-content {
        margin-left: 70px;
        width: calc(100% - 70px);
    }

    .topbar {
        padding: 0 20px;
    }

    .breadcrumb {
        display: none;
    }

    .mobile-brand {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .mobile-brand .sidebar-logo {
        width: 30px;
        height: 30px;
    }

    .mobile-brand strong {
        font-family:
            'Space Grotesk',
            sans-serif;

        font-size: 13px;
    }

    .content-wrapper {
        padding: 25px 20px 45px;
    }
}

@media (max-width: 620px) {
    .login-screen {
        padding: 15px;
    }

    .login-card {
        padding: 27px 22px;
    }

    .login-heading h1 {
        font-size: 23px;
    }

    .topbar-profile > div:last-child {
        display: none;
    }

    .welcome-section,
    .page-header {
        align-items: flex-start;
        flex-direction: column;
        gap: 17px;
    }

    .welcome-section h1,
    .page-header h1 {
        font-size: 25px;
    }

    .welcome-section .btn,
    .page-header .btn {
        width: 100%;
    }

    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }

    .stat-card {
        padding: 14px;
        gap: 9px;
    }

    .stat-icon {
        width: 36px;
        height: 36px;
    }

    .stat-content strong {
        font-size: 19px;
    }

    .court-grid {
        grid-template-columns: 1fr;
    }

    .section-heading {
        align-items: flex-start;
        gap: 10px;
    }

    .table-card {
        border-radius: 12px;
    }

    .table-scroll {
        overflow-x: auto;
    }

    .data-table {
        min-width: 700px;
    }

    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }

    .modal-card {
        max-height: 92vh;
        overflow-y: auto;
    }

    .modal-header,
    .booking-form {
        padding-left: 20px;
        padding-right: 20px;
    }

    .modal-actions {
        flex-direction: column-reverse;
    }

    .modal-actions .btn {
        width: 100%;
    }
}

@media (max-width: 430px) {
    .sidebar {
        width: 58px;
    }

    .main-content {
        margin-left: 58px;
        width: calc(100% - 58px);
    }

    .topbar {
        height: 60px;
        padding: 0 13px;
    }

    .content-wrapper {
        padding: 22px 13px 35px;
    }

    .stats-grid {
        gap: 8px;
    }

    .stat-card {
        flex-direction: column;
        align-items: flex-start;
    }

    .stat-content small {
        display: none;
    }

    .login-brand {
        margin-bottom: 18px;
    }
}
</style>