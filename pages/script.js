// Apple-style smooth header scroll effect
let lastScroll = 0;
const header = document.querySelector('.header');

window.addEventListener('scroll', () => {
    const currentScroll = window.pageYOffset;
    
    if (currentScroll > 80) {
        header.classList.add('header--scrolled');
    } else {
        header.classList.remove('header--scrolled');
    }
    
    lastScroll = currentScroll;
}, { passive: true });

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Intersection Observer for fade-in animations
const observerOptions = {
    threshold: 0.15,
    rootMargin: '0px 0px -100px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe sections for animation
document.querySelectorAll('section').forEach(section => {
    observer.observe(section);
});

// Animate steps on scroll
const stepObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('fade-in-visible');
        }
    });
}, observerOptions);

document.querySelectorAll('.step').forEach(step => {
    stepObserver.observe(step);
});

// Animated Counter for Statistics Section (Elegant Style)
function animateCounter(element) {
    const target = parseFloat(element.getAttribute('data-target'));
    const duration = 2000; // 2 seconds
    const increment = target / (duration / 16); // 60fps
    let current = 0;
    
    const updateCounter = () => {
        current += increment;
        if (current < target) {
            // Handle decimals for numbers like 2.5
            if (target < 10 && target % 1 !== 0) {
                element.textContent = current.toFixed(1);
            } else {
                element.textContent = Math.floor(current);
            }
            requestAnimationFrame(updateCounter);
        } else {
            // Final value
            if (target < 10 && target % 1 !== 0) {
                element.textContent = target.toFixed(1);
            } else {
                element.textContent = Math.floor(target);
            }
        }
    };
    
    updateCounter();
}

// Observe statistics section (Elegant Cards)
const statsObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const numbers = entry.target.querySelectorAll('.stat-number');
            numbers.forEach((num, index) => {
                setTimeout(() => {
                    animateCounter(num);
                }, index * 150); // Stagger animation
            });
            statsObserver.unobserve(entry.target); // Only animate once
        }
    });
}, {
    threshold: 0.3
});

const statsSection = document.querySelector('.statistics-section');
if (statsSection) {
    statsObserver.observe(statsSection);
}

// Elegant Card Hover Effects
document.querySelectorAll('.stat-elegant-card, .use-case-elegant-card').forEach(card => {
    card.addEventListener('mouseenter', () => {
        card.style.transition = 'all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1)';
    });
});

const stepsObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const steps = entry.target.querySelectorAll('.step');
            steps.forEach((step, index) => {
                setTimeout(() => {
                    step.style.opacity = '1';
                    step.style.transform = 'translateY(0)';
                }, index * 200);
            });
        }
    });
}, { threshold: 0.2 });

const stepsContainer = document.querySelector('.steps-container');
if (stepsContainer) {
    // Initialize steps hidden
    document.querySelectorAll('.step').forEach(step => {
        step.style.opacity = '0';
        step.style.transform = 'translateY(40px)';
        step.style.transition = 'all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1)';
    });
    stepsObserver.observe(stepsContainer);
}

// Chatbot functionality
document.addEventListener('DOMContentLoaded', () => {
    const chatbot = document.querySelector('.chatbot');
    if (!chatbot) {
        return;
    }

    const toggleBtn = chatbot.querySelector('.chatbot-toggle');
    const closeBtn = chatbot.querySelector('.chatbot-close');
    const windowEl = chatbot.querySelector('.chatbot-window');
    const messagesEl = chatbot.querySelector('.chatbot-messages');
    const form = chatbot.querySelector('.chatbot-form');
    const input = chatbot.querySelector('#chatbot-input');

    const state = {
        openedOnce: false
    };

    const knowledgeBase = [
        {
            pattern: /(xin chào|chào|hi|hello)/i,
            response: 'Xin chào! Mình là Victoria Assistant. Bạn cần hỗ trợ điều gì hôm nay?'
        },
        {
            pattern: /(giá|pricing|chi phí|bao nhiêu)/i,
            response: 'Gói cơ bản của chúng tôi hoàn toàn miễn phí. Các gói Pro bắt đầu từ 299.000đ/tháng với nhiều tính năng nâng cao.'
        },
        {
            pattern: /(liên hệ|contact|support|hỗ trợ)/i,
            response: 'Bạn có thể liên hệ đội ngũ hỗ trợ qua email support@victoria.ai hoặc gọi hotline 1900-123-456.'
        },
        {
            pattern: /(tính năng|feature|làm được gì)/i,
            response: 'Victoria AI cung cấp phân tích dữ liệu tự động, gợi ý hành động và báo cáo realtime để giúp bạn đưa ra quyết định nhanh hơn.'
        },
        {
            pattern: /(đăng ký|signup|bắt đầu|create account)/i,
            response: 'Để bắt đầu, bạn chỉ cần nhấn nút Get Started ở đầu trang và làm theo hướng dẫn đăng ký tài khoản.'
        },
        {
            pattern: /(demo|thử|dùng thử|test)/i,
            response: 'Bạn có thể sử dụng mục “Thử Nghiệm Ngay” trên trang để upload dữ liệu và xem kết quả demo trong vài giây.'
        },
        {
            pattern: /(bảo mật|an toàn|security)/i,
            response: 'Chúng tôi sử dụng mã hóa đầu-cuối và tuân thủ các tiêu chuẩn bảo mật ISO 27001 để bảo vệ dữ liệu của bạn.'
        },
        {
            pattern: /(cảm ơn|thanks|thank you)/i,
            response: 'Rất vui được hỗ trợ bạn! Nếu còn câu hỏi gì, cứ tiếp tục trò chuyện nhé.'
        }
    ];

    const fallbackResponses = [
        'Mình chưa hiểu câu hỏi lắm, bạn có thể mô tả chi tiết hơn không?',
        'Câu hỏi hay đấy! Bạn có thể cho mình thêm thông tin để hỗ trợ tốt hơn chứ?',
        'Hiện mình chưa có câu trả lời chính xác. Bạn thử để lại email để đội ngũ chuyên gia liên hệ nhé?'
    ];

    const getBotResponse = (message) => {
        const matched = knowledgeBase.find(item => item.pattern.test(message));
        if (matched) {
            return matched.response;
        }
        const randomIndex = Math.floor(Math.random() * fallbackResponses.length);
        return fallbackResponses[randomIndex];
    };

    const addMessage = (text, sender = 'bot') => {
        const bubble = document.createElement('div');
        bubble.className = `chatbot-message ${sender}`;
        bubble.textContent = text;
        messagesEl.appendChild(bubble);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    };

    const openChat = () => {
        if (chatbot.classList.contains('is-open')) {
            return;
        }
        chatbot.classList.add('is-open');
        toggleBtn.setAttribute('aria-expanded', 'true');
        windowEl.setAttribute('aria-modal', 'true');
        windowEl.setAttribute('aria-hidden', 'false');

        if (!state.openedOnce) {
            addMessage('Chào bạn! Mình là Victoria Assistant. Hôm nay mình có thể giúp gì cho bạn?');
            state.openedOnce = true;
        }

        setTimeout(() => {
            input.focus();
        }, 120);
    };

    const closeChat = () => {
        if (!chatbot.classList.contains('is-open')) {
            return;
        }
        chatbot.classList.remove('is-open');
        toggleBtn.setAttribute('aria-expanded', 'false');
        windowEl.setAttribute('aria-modal', 'false');
        windowEl.setAttribute('aria-hidden', 'true');
        toggleBtn.focus();
    };

    const handleSubmit = (event) => {
        event.preventDefault();
        const userMessage = input.value.trim();
        if (!userMessage) {
            return;
        }

        addMessage(userMessage, 'user');
        input.value = '';

        setTimeout(() => {
            addMessage(getBotResponse(userMessage), 'bot');
        }, 350);
    };

    toggleBtn.addEventListener('click', () => {
        if (chatbot.classList.contains('is-open')) {
            closeChat();
        } else {
            openChat();
        }
    });

    closeBtn.addEventListener('click', closeChat);
    form.addEventListener('submit', handleSubmit);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeChat();
        }
    });

    document.addEventListener('click', (event) => {
        if (!chatbot.contains(event.target) && chatbot.classList.contains('is-open')) {
            closeChat();
        }
    });

    windowEl.setAttribute('aria-hidden', 'true');
});

// ============================================
// 3D Tilt Effect for Tech Cards
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    const techCards = document.querySelectorAll('[data-tilt]');
    
    techCards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = ((y - centerY) / centerY) * 8; // Max 8 degrees
            const rotateY = ((centerX - x) / centerX) * 8;
            
            card.style.transform = `
                translateY(-12px) 
                rotateX(${rotateX}deg) 
                rotateY(${rotateY}deg)
                scale(1.02)
            `;
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0) rotateX(0) rotateY(0) scale(1)';
        });
    });
    
    // Enhanced particle animation trigger
    const techSection = document.querySelector('.tech-stack');
    if (techSection) {
        const techObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                }
            });
        }, { threshold: 0.2 });
        
        techObserver.observe(techSection);
    }
});
