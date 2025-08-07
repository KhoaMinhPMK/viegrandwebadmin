#!/bin/bash

# Script để xóa các file không có nội dung (file rỗng)
# Author: Auto-generated
# Date: $(date)

echo "🔍 Đang tìm kiếm các file rỗng trong thư mục hiện tại..."

# Tìm và hiển thị danh sách các file rỗng
echo "📋 Danh sách các file rỗng được tìm thấy:"
find . -type f -empty -print

# Đếm số lượng file rỗng
empty_count=$(find . -type f -empty | wc -l)
echo "📊 Tổng số file rỗng: $empty_count"

if [ $empty_count -eq 0 ]; then
    echo "✅ Không có file rỗng nào được tìm thấy!"
    exit 0
fi

# Hỏi xác nhận trước khi xóa
echo ""
read -p "❓ Bạn có muốn xóa tất cả các file rỗng này không? (y/N): " confirm

case $confirm in
    [yY]|[yY][eE][sS])
        echo "🗑️  Đang xóa các file rỗng..."
        
        # Xóa các file rỗng và hiển thị kết quả
        deleted_count=0
        while IFS= read -r -d '' file; do
            if [ -f "$file" ]; then
                echo "🗑️  Xóa: $file"
                rm "$file"
                ((deleted_count++))
            fi
        done < <(find . -type f -empty -print0)
        
        echo "✅ Đã xóa thành công $deleted_count file rỗng!"
        ;;
    *)
        echo "❌ Hủy bỏ thao tác xóa."
        ;;
esac

echo "🏁 Hoàn thành!"
