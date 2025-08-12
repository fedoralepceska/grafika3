<template>
    <div
        class="status-card"
        :class="{ clickable: !!link, compact, inline, stacked }"
        :role="link ? 'button' : 'region'"
        :tabindex="link ? 0 : -1"
        @click="openLink"
        @keydown.enter.prevent="openLink"
        @keydown.space.prevent="openLink"
        :aria-label="title"
        :title="title"
        :style="{ '--icon-size': iconSizePx }"
    >
        <div class="icon-ring" :class="{ compact, inline }" :style="{ border: borderColor }">
            <i :class="icon" :style="{ color: color }" aria-hidden="true" />
        </div>
        <div class="status-content" :class="{ compact, inline, stacked }">
            <div class="status-number text-white" :class="{ compact, inline, stacked }">{{ number }}</div>
            <div class="status-text" :class="{ compact, inline, stacked }">{{ title }}</div>
        </div>
    </div>
</template>

<script>
export default {
    name: "StatusBox",
    props: {
        icon: String,
        title: String,
        number: [Number, String],
        color: String,
        borderColor:{
            type:String,
            default: '2px solid #0073a9',
        },
        link: String,
        compact: {
            type: Boolean,
            default: false
        },
        inline: {
            type: Boolean,
            default: false
        },
        stacked: {
            type: Boolean,
            default: false
        }
    },
    computed: {
        iconSizePx() {
            if (this.inline) return '36px';
            if (this.compact) return '40px';
            return '64px';
        }
    },
    methods: {
        openLink() {
            if (!this.link) return;
            window.open(this.link, '_self');
        }
    }
}
</script>

<style scoped lang="scss">
.status-card {
    display: grid;
    grid-template-columns: auto 1fr;
    align-items: center;
    gap: 16px;
    background-color: $light-gray;
    border-radius: 12px;
    padding: 16px;
    border: 1px solid rgba(255, 255, 255, 0.06);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.18);
    transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
    min-height: 96px;
}

.status-card.inline {
    padding: 10px 12px;
    min-height: 64px;
    gap: 12px;
    border-radius: 10px;
}

/* stacked keeps default grid columns; only content stacks vertically */



.status-card.clickable { cursor: pointer; }

.status-card.clickable:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.25);
}

.status-card:focus-visible {
    outline: 2px solid rgba(255, 255, 255, 0.35);
    outline-offset: 3px;
}

.icon-ring {
    width: var(--icon-size);
    height: var(--icon-size);
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #0073a9;
}

/* sizes handled via --icon-size */

.status-content { min-width: 0; }

.status-content.inline {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 8px;
}

.status-content.stacked {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: center;
    gap: 2px;
    text-align: left;
}

.status-number {
    font-weight: 800;
    line-height: 1;
    font-size: clamp(1.5rem, 3.5vw, 2.25rem);
}

.status-number.compact {
    font-size: clamp(1.1rem, 2.5vw, 1.5rem);
}

.status-number.inline {
    font-size: clamp(1rem, 2.2vw, 1.35rem);
}

.status-number.stacked { font-size: clamp(1.1rem, 2.5vw, 1.6rem); }

.status-text {
    color: rgba(255, 255, 255, 0.85);
    margin-top: 4px;
    font-size: clamp(0.8rem, 1.8vw, 1rem);
}

.status-content.compact { gap: 0; }
.status-text.compact {
    font-size: clamp(0.7rem, 1.6vw, 0.85rem);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.status-text.inline {
    margin-top: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: clamp(0.8rem, 1.7vw, 0.95rem);
}

.status-text.stacked {
    margin-top: 2px;
    font-size: clamp(0.75rem, 1.6vw, 0.95rem);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

@media (max-width: 420px) {
    .icon-ring { width: 56px; height: 56px; }
    .status-card { padding: 12px; gap: 12px; min-height: 80px; }
}
</style>
