<template>
    <div>
        <h3 class="order-history-title uppercase">{{ $t('Order History') }}</h3>
        <div class="history-subtitle">{{ invoice.invoice_title }} #{{ invoice.id }}</div>
        <div class="order-history-content">
            <ul class="order-history-list">
                <li v-for="log in invoice.history_logs" :key="log.id" class="box">
                    <div class="order-history-item">
                        <img src="../../../../public/images/UserLogo.png" style="width: 30px; height: 30px">
                        <span class="log-action font-weight-black" style="font-weight: bolder">{{ invoice.user.name }}</span>
                    </div>
                    <h3 style="padding-bottom: 12px">{{ log.action }}</h3>
                    Date: {{ new Date(log.created_at).toLocaleString() }}
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
export default {
    name: "OrderHistory",

    props: {
        invoice: Object
    }
}
</script>

<style scoped lang="scss">
.order-history-title {
    margin-bottom: 10px;
    font-size: 1.2em;
}

.order-history-list {
    list-style-type: none;
    padding: 0;
}

.box {
    padding: 12px;
    border-bottom: 1px solid #444;
    background-color: $ultra-light-gray;
}

.order-history-item {
    gap: 12px;
    display: flex;
    align-items: center;
    padding-bottom: 12px;
}

.log-date {
    font-weight: bold;
    margin-right: 5px;
}

.log-action {
    color: white;
}
.history-subtitle {
    background-color: white;
    color: black;
    padding: 10px;
    margin-bottom: 10px;
    font-weight: bold;
}
</style>
