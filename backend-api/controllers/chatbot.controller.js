const db = require('../config/db');

// Bảng keyword và reply đơn giản rule-based
const CHAT_RULES = [
  {
    keywords: ['xin chào', 'chào', 'hi', 'hello', 'hey'],
    reply: 'Xin chào! Chào mừng bạn đến với ADN Travel. Tôi có thể giúp gì cho bạn hôm nay? 😊',
  },
  {
    keywords: ['tour', 'danh sách tour', 'có tour nào', 'tour nào'],
    reply: 'Chúng tôi có nhiều tour hấp dẫn: Đà Nẵng, Phú Quốc, Đà Lạt và nhiều điểm đến khác. Bạn có thể xem danh sách tour tại trang chủ của chúng tôi!',
  },
  {
    keywords: ['giá', 'bao nhiêu tiền', 'chi phí', 'phí'],
    reply: 'Giá tour của chúng tôi rất cạnh tranh, dao động từ 1.5 triệu đến hàng chục triệu đồng tùy tour. Bạn vui lòng xem chi tiết từng tour để biết giá cụ thể nhé!',
  },
  {
    keywords: ['đặt tour', 'booking', 'đăng ký tour', 'mua tour'],
    reply: 'Để đặt tour, bạn cần: 1) Tạo tài khoản / Đăng nhập. 2) Chọn tour yêu thích. 3) Điền thông tin và xác nhận. 4) Thanh toán. Rất đơn giản phải không? 😊',
  },
  {
    keywords: ['thanh toán', 'chuyển khoản', 'momo', 'vnpay', 'tiền'],
    reply: 'Chúng tôi hỗ trợ các hình thức thanh toán: Tiền mặt (CASH), Chuyển khoản ngân hàng (BANK_TRANSFER), MoMo và VNPay.',
  },
  {
    keywords: ['hủy tour', 'hủy booking', 'cancel', 'hoàn tiền'],
    reply: 'Bạn có thể hủy booking ở trạng thái PENDING trong phần lịch sử đặt tour. Trường hợp booking đã CONFIRMED, vui lòng liên hệ hotline để được hỗ trợ.',
  },
  {
    keywords: ['da nang', 'đà nẵng', 'da nẵng'],
    reply: 'Tour Đà Nẵng là lựa chọn tuyệt vời! Bao gồm tham quan Bà Nà Hills, Hội An, biển Mỹ Khê. Thời gian thường là 3N2Đ. Bạn muốn biết thêm chi tiết không?',
  },
  {
    keywords: ['phu quoc', 'phú quốc'],
    reply: 'Tour Phú Quốc - thiên đường biển đảo! Bao gồm tắm biển, lặn ngắm san hô, khám phá đảo. Thời gian phổ biến là 4N3Đ. Rất phù hợp cho gia đình và cặp đôi!',
  },
  {
    keywords: ['da lat', 'đà lạt', 'dalat'],
    reply: 'Tour Đà Lạt - thành phố ngàn hoa! Săn mây, thưởng thức cà phê, khám phá thác nước. Thời gian thường 2N1Đ rất phù hợp cho cuối tuần.',
  },
  {
    keywords: ['liên hệ', 'hotline', 'điện thoại', 'email', 'contact'],
    reply: 'Bạn có thể liên hệ với chúng tôi qua:\n📧 Email: info@adntravel.vn\n📞 Hotline: 1900 xxxx\n🕐 Giờ làm việc: 8:00 - 18:00 (Thứ 2 - Thứ 7)',
  },
  {
    keywords: ['cảm ơn', 'thanks', 'thank you', 'tạm biệt', 'bye'],
    reply: 'Cảm ơn bạn đã sử dụng dịch vụ của ADN Travel! Chúc bạn có những chuyến đi thật vui vẻ và ý nghĩa. 🌟',
  },
];

function findReply(message = '') {
  const normalized = message.toLowerCase().trim();

  for (const rule of CHAT_RULES) {
    const matched = rule.keywords.some((kw) => normalized.includes(kw.toLowerCase()));
    if (matched) {
      return rule.reply;
    }
  }

  return 'Xin lỗi, tôi chưa hiểu câu hỏi của bạn. Bạn có thể hỏi về: danh sách tour, giá tour, cách đặt tour, thanh toán, hoặc liên hệ hotline để được hỗ trợ trực tiếp nhé! 😊';
}

// POST /api/chatbot/message
// Gửi tin nhắn và nhận phản hồi từ chatbot
exports.sendMessage = async (req, res) => {
  const { message } = req.body;

  if (!message || !String(message).trim()) {
    return res.status(400).json({ message: 'Vui long nhap noi dung tin nhan.' });
  }

  try {
    const reply = findReply(String(message));

    return res.status(200).json({
      message: 'Nhan phan hoi thanh cong.',
      data: {
        userMessage: String(message).trim(),
        reply,
        timestamp: new Date(),
      },
    });
  } catch (error) {
    return res.status(500).json({ message: 'Chatbot dang gap su co.', error: error.message });
  }
};
