
收银项目：


git常用命令


git clone /克隆代码库
git add -A 添加所有文件
git commit -m '注释'  提交（需要编写日志）
git push origin p2p 推送到p2p分支
ssh-keygen -t rsa -C "邮箱" 配置密钥 

git branch -a 列出所有分支
git checkout dev 切换分支命令，dev是要切换的分支名称
git config --global user.name "username"
git config --global user.email "email"
git config --list 查看git配置 

git push origin master     代码推送
git push -u origin dev      代码推送

git pull origin master       代码下拉 
git pull origin dev            代码下拉 

git push -f 强制推送
git diff 可以查看当前目录的所有修改。
git checkout -b 创建分支
git merge 合并分支
git commit -m '项目初始化' -m写注释